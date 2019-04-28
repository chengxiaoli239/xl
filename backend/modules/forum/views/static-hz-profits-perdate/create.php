<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\StaticHzProfitsPerdate */

$this->title = Yii::t('app', 'Create Static Hz Profits Perdate');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Hz Profits Perdates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-hz-profits-perdate-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
