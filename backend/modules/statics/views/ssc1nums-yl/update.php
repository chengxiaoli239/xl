<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\statics\Ssc1numsYl */

$this->title = 'Update Ssc1nums Yl: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Ssc1nums Yls', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="ssc1nums-yl-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
