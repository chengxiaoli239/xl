<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\wechat\RobotUser */

$this->title = 'Create Robot User';
$this->params['breadcrumbs'][] = ['label' => 'Robot Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="robot-user-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
