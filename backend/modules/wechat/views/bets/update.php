<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\Bets */

$this->title = 'Update Bets: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Bets', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="bets-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
