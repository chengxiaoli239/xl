<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\open\PlatformRobot */

$this->title = 'Update Platform Robot: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Platform Robots', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="platform-robot-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
