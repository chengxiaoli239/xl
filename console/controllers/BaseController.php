<?php

namespace console\controllers;

use yii\base\Module;
use yii\console\Controller;

class BaseController extends Controller
{
    public function __construct($id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
    }

}
