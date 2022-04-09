<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\LotteryDataDealStatus */

$this->title = Yii::t('app', 'Update Lottery Data Deal Status: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Lottery Data Deal Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="lottery-data-deal-status-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
