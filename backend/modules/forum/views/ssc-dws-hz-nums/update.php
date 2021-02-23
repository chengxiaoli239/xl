<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SscDwsHzNums */

$this->title = Yii::t('app', 'Update Ssc Dws Hz Nums: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dws Hz Nums'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="ssc-dws-hz-nums-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
