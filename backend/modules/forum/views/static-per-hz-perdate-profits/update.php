<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticPerHzPerdateProfits */

$this->title = Yii::t('app', 'Update Static Per Hz Perdate Profits: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Per Hz Perdate Profits'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="static-per-hz-perdate-profits-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
