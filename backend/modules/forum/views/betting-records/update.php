<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\BettingRecords */

$this->title = 'Update Betting Records: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Betting Records', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="betting-records-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
