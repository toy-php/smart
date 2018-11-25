<?php

namespace core\http;

use exceptions\SessionException;

const UINT32_LE_PACK_CODE = "V";
const UINT32_SIZE = 4;
const RFC2965_COOKIE_SIZE = 4096;
const MIN_OVERHEAD_PER_COOKIE = 3;
const METADATA_SIZE = UINT32_SIZE;

if (!function_exists('hash_equals')) {
    function hash_equals($a, $b)
    {
        $ret = strlen($a) ^ strlen($b);
        $ret |= array_sum(unpack("C*", $a ^ $b));
        return !$ret;
    }
}

/**
 * Class CryptoCookieSessionHandler
 * @package core\http
 * @see https://github.com/Snawoot/php-storageless-sessions
 */
final class CryptoCookieSessionHandler implements \SessionHandlerInterface
{

    private $secret;
    private $digestAlgo;
    private $digestLen;
    private $cipherAlgo;
    private $cipherIvLen;
    private $sessionNameLen;
    private $sessionCookieParams;
    private $overWritten = [];
    private $opened = false;
    private $cipherKeyLen;
    private $expire;

    /**
     * CryptoCookieSessionHandler constructor.
     * @param string $secret
     * @param int $expire
     * @param string $digestAlgo
     * @param string $cipherAlgo
     * @param int $cipherKeyLen
     * @throws SessionException
     */
    public function __construct(
        string $secret,
        int $expire = 2592000,
        string $digestAlgo = "sha256",
        string $cipherAlgo = "aes-256-ctr",
        int $cipherKeyLen = 32
    )
    {
        if (empty($secret)) {
            throw new SessionException('Секретный ключ не может быть пустым');
        }
        $this->secret = $secret;
        if (!in_array($digestAlgo, hash_algos())) {
            throw new SessionException('Ошибка алгоритма хеширования');
        }
        $this->digestAlgo = $digestAlgo;
        if (!in_array($cipherAlgo, openssl_get_cipher_methods(true))) {
            throw new SessionException('Ошибка алгоритма хеширования');
        }
        $this->cipherAlgo = $cipherAlgo;
        if (!(is_int($cipherKeyLen) && is_int($expire) && $expire > 0 && $cipherKeyLen > 0)) {
            throw new SessionException('Неверные значения аргументов');
        }
        $this->cipherKeyLen = $cipherKeyLen;
        $this->expire = $expire;
    }

    /**
     * Close the session
     * @link http://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close()
    {
        return true;
    }

    /**
     * Destroy a session
     * @link http://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $sessionId The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($sessionId)
    {
        setcookie($sessionId, '', time() - 1000);
        setcookie($sessionId, '', time() - 1000, '/');
        unset($this->overWritten[$sessionId]);
        return true;
    }

    /**
     * Cleanup old sessions
     * @link http://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxLifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxLifetime)
    {
        return true;
    }

    /**
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $savePath The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     * @throws SessionException
     */
    public function open($savePath, $name)
    {
        if (ob_get_level() === 0) ob_start();
        $this->digestLen = strlen(hash($this->digestAlgo, "", true));
        $this->cipherIvLen = openssl_cipher_iv_length($this->cipherAlgo);
        if ($this->digestLen === false or $this->cipherIvLen === false) throw new SessionException('Ошибка алгоритма хеширования');
        $this->sessionNameLen = strlen(session_name());
        $this->sessionCookieParams = session_get_cookie_params();
        $this->opened = true;
        return true;
    }

    /**
     * Read session data
     * @link http://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $sessionId The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     * @throws SessionException
     */
    public function read($sessionId)
    {
        if (!$this->opened) $this->open("", "");
        if (isset($this->overWritten[$sessionId])) {
            list($ovr_data, $ovr_expires) = $this->overWritten[$sessionId];
            return (time() < $ovr_expires) ? $ovr_data : "";
        }
        if (!isset($_COOKIE[$sessionId])) {
            return "";
        }
        $input = $this->base64_urlsafe_decode($_COOKIE[$sessionId]);
        if ($input === false) {
            return "";
        }
        $digest = substr($input, 0, $this->digestLen);
        if ($digest === false) return "";
        $message = substr($input, $this->digestLen);
        if ($message === false) return "";

        if (!hash_equals(
            hash_hmac($this->digestAlgo, $sessionId . $message, $this->secret, true),
            $digest)) {
            return "";
        }
        $valid_till_bin = substr($message, 0, METADATA_SIZE);
        $valid_till = unpack(UINT32_LE_PACK_CODE, $valid_till_bin)[1];
        if (time() > $valid_till) {
            return "";
        }
        $iv = substr($message, METADATA_SIZE, $this->cipherIvLen);
        $ciphertext = substr($message, METADATA_SIZE + $this->cipherIvLen);
        $key = $this->pbkdf2($this->digestAlgo, $this->secret, $sessionId . $valid_till_bin, 1, $this->cipherKeyLen, true);
        $data = openssl_decrypt($ciphertext, $this->cipherAlgo, $key, OPENSSL_RAW_DATA, $iv);
        if ($data === false) {
            throw new SessionException('Ошибка SSL');
        }
        return $data;
    }

    /**
     * Write session data
     * @link http://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $sessionId The session id.
     * @param string $sessionData <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     * @throws SessionException
     */
    public function write($sessionId, $sessionData)
    {
        if (!$this->opened) $this->open("", "");
        $expires = time() + $this->expire;
        $valid_till_bin = pack(UINT32_LE_PACK_CODE, $expires);
        $iv = openssl_random_pseudo_bytes($this->cipherIvLen);
        $key = $this->pbkdf2($this->digestAlgo, $this->secret, $sessionId . $valid_till_bin, 1, $this->cipherKeyLen, true);
        $ciphertext = openssl_encrypt($sessionData, $this->cipherAlgo, $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new SessionException('Ошибка SSL');
        }
        $meta = $valid_till_bin;
        $message = $meta . $iv . $ciphertext;
        $digest = hash_hmac($this->digestAlgo, $sessionId . $message, $this->secret, true);
        $output = $this->base64_urlsafe_encode($digest . $message);

        if ((strlen($output) +
                $this->sessionNameLen +
                strlen($sessionId) +
                2 * MIN_OVERHEAD_PER_COOKIE) > RFC2965_COOKIE_SIZE
        )
            throw new SessionException('Размер данных превысил допустимую величину');
        $this->overWritten[$sessionId] = array($sessionData, $expires);
        return setcookie($sessionId,
            $output,
            ($this->sessionCookieParams["lifetime"] > 0) ? time() + $this->sessionCookieParams["lifetime"] : 0,
            $this->sessionCookieParams["path"],
            $this->sessionCookieParams["domain"],
            $this->sessionCookieParams["secure"],
            $this->sessionCookieParams["httponly"]
        );
    }

    /*
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     * $algorithm - The hash algorithm to use. Recommended: SHA256
     * $password - The password.
     * $salt - A salt that is unique to the password.
     * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
     * $key_length - The length of the derived key in bytes.
     * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
     * Returns: A $key_length-byte key derived from the password and salt.
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     *
     * This implementation of PBKDF2 was originally created by https://defuse.ca
     * With improvements by http://www.variations-of-shadow.com
     */
    private function pbkdf2($algorithm, $password, $salt, $count, $keyLength, $rawOutput = false)
    {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true))
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        if ($count <= 0 || $keyLength <= 0)
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
        if (function_exists("hash_pbkdf2")) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            if (!$rawOutput) {
                $keyLength = $keyLength * 2;
            }
            return hash_pbkdf2($algorithm, $password, $salt, $count, $keyLength, $rawOutput);
        }
        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($keyLength / $hash_length);
        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }
        if ($rawOutput)
            return substr($output, 0, $keyLength);
        else
            return bin2hex(substr($output, 0, $keyLength));
    }

    private function base64_urlsafe_encode($input)
    {
        return strtr(base64_encode($input), array("+" => "-", "/" => "_", "=" => ""));
    }

    private function base64_urlsafe_decode($input)
    {
        $translated = strtr($input, array("-" => "+", "_" => "/"));
        $padded = str_pad($translated, ((int)((strlen($input) + 3) / 4)) * 4, "=", STR_PAD_RIGHT);
        return base64_decode($padded);
    }

}