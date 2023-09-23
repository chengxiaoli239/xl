<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\RobotUser */

$this->title = 'Update Robot User: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Robot Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="robot-user-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
