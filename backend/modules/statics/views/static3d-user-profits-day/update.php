<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\statics\Static3dUserProfitsDay */

$this->title = 'Update Static3d User Profits Day: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Static3d User Profits Days', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="static3d-user-profits-day-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
