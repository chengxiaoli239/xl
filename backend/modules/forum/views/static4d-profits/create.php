<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Static4dProfits */

$this->title = Yii::t('app', 'Create Static4d Profits');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static4d Profits'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static4d-profits-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
