<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SscDwHzYl */

$this->title = Yii::t('app', 'Update Ssc Dw Hz Yl: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dw Hz Yls'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="ssc-dw-hz-yl-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
