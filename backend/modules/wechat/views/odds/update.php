<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\Odds */

$this->title = 'Update Odds: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Odds', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="odds-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
