<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\UserCustomPlans */

$this->title = Yii::t('app', 'playway '.$playway.' Plans');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Custom Plans'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-custom-plans-create wrapper site-min-height">

    <?= $this->render('_form_'.$playway, [
        'model' => $model,
    ]) ?>

</div>
