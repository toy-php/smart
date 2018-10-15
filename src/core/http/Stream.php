<?php

namespace core\http;

use Psr\Http\Message\StreamInterface;
use exceptions\Exception;

class Stream implements StreamInterface
{

    protected $stream;
    private static $readWriteHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        ]
    ];

    /**
     * Stream constructor.
     * @param $stream
     * @throws Exception
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new Exception('Поток должен быть ресурсом');
        }
        $this->stream = $stream;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        try {
            $this->seek(0);
            return (string) stream_get_contents($this->stream);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        if (!empty($this->stream)) {
            fclose($this->stream);
            $this->detach();
        }
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }
        $stream = $this->stream;
        $this->stream = null;
        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        $stats = fstat($this->stream);
        return isset($stats['size']) ? $stats['size'] : null;
    }

    /**
     * @inheritdoc
     */
    public function tell()
    {
        $result = ftell($this->stream);
        if ($result === false) {
            throw new Exception('Невозможно определить позицию потока');
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * @inheritdoc
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable') ?: false;
    }

    /**
     * @inheritdoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new Exception('Невозможно производить поиск в потоке');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            throw new Exception('Не найдена позиция в потоке '
                . $offset . ' со значением ' . var_export($whence, true));
        }
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * @inheritdoc
     */
    public function isWritable()
    {
        return isset(self::$readWriteHash['write'][$this->getMetadata('mode')]);
    }

    /**
     * @inheritdoc
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new Exception('Поток не может быть записан');
        }
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new Exception('Записи в поток не прозошло');
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function isReadable()
    {
        return isset(self::$readWriteHash['read'][$this->getMetadata('mode')]);
    }

    /**
     * @inheritdoc
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new Exception('Поток не может быть прочитан');
        }
        return fread($this->stream, $length);
    }

    /**
     * @inheritdoc
     */
    public function getContents()
    {
        $this->rewind();
        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new Exception('Контент потока не прочитан');
        }
        return $contents;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null)
    {
        if (empty($this->stream)) {
            return $key ? null : [];
        } elseif (!$key) {
            return stream_get_meta_data($this->stream);
        }
        $meta = stream_get_meta_data($this->stream);
        return isset($meta[$key]) ? $meta[$key] : null;
    }
}