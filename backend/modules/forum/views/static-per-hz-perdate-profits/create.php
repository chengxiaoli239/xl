<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\StaticPerHzPerdateProfits */

$this->title = Yii::t('app', 'Create Static Per Hz Perdate Profits');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Per Hz Perdate Profits'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-per-hz-perdate-profits-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
