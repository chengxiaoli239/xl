<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\statics\StaticPositionTypeArisePerdate */

$this->title = 'Update Static Position Type Arise Perdate: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Static Position Type Arise Perdates', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="static-position-type-arise-perdate-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
