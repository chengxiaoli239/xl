<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\UserCustomPlans */

$this->title = Yii::t('app', 'Update User Custom Plans: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Custom Plans'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="user-custom-plans-update wrapper site-min-height">

    <?= $this->render('_form_'.$playway, [
        'model' => $model,
    ]) ?>

</section>
