<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscDwsHzNums */

$this->title = Yii::t('app', 'Create Ssc Dws Hz Nums');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dws Hz Nums'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-dws-hz-nums-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
