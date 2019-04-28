<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\StaticHzProfits */

$this->title = Yii::t('app', 'Create Static Hz Profits');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Hz Profits'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-hz-profits-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
