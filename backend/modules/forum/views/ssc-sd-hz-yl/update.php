<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SscSdHzYl */

$this->title = Yii::t('app', 'Update Ssc Sd Hz Yl: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Sd Hz Yls'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="ssc-sd-hz-yl-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
