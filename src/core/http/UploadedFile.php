<?php

namespace core\http;

use Psr\Http\Message\UploadedFileInterface;
use exceptions\Exception;

class UploadedFile implements UploadedFileInterface
{

    protected $stream;
    protected $file;
    protected $size;
    protected $error;
    protected $clientFilename;
    protected $clientMediaType;
    protected $moved = false;

    /**
     * UploadedFile constructor.
     * @param array $file
     * @throws Exception
     */
    public function __construct(array $file)
    {
        if(is_uploaded_file($file['tmp_name'])){
            $this->stream = $this->createStream($file['tmp_name']);
            $this->file = $file['tmp_name'];
            $this->size = $file['size'];
            $this->error = $file['error'];
            $this->clientFilename = $file['name'];
            $this->clientMediaType = $file['type'];
        }else{
            $this->error = UPLOAD_ERR_NO_FILE;
        }
    }

    /**
     * Создание объекта потока
     * @param $file
     * @return null|Stream
     * @throws Exception
     */
    protected function createStream($file)
    {
        if($this->isOk()){
            return new Stream(fopen($file, 'r'));
        }
        return null;
    }

    protected function isOk()
    {
        return $this->getError() === UPLOAD_ERR_OK;
    }

    /**
     * @inheritdoc
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @inheritdoc
     */
    public function moveTo($targetPath)
    {
        if($this->isOk() and !$this->moved){
            move_uploaded_file($this->file, $targetPath);
            $this->moved = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * @inheritdoc
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}