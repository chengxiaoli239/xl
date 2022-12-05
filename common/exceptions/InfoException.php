<?php
namespace common\exceptions;

class InfoException extends \Exception
{
    public $data = [];

    public function setData($data)
    {
        $this->data = $data;
    }
}