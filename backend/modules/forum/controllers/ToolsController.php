<?php

namespace backend\modules\forum\controllers;

class ToolsController extends \backend\controllers\BaseController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
