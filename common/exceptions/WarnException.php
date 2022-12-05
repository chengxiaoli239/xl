<?php
namespace common\exceptions;

class WarnException extends \Exception
{
    public $data = [];

    public function setData($data)
    {
        $this->data = $data;
    }
}