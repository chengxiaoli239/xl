<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Static4dProfitsPerdate */

$this->title = Yii::t('app', 'Update Static4d Profits Perdate: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static4d Profits Perdates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="static4d-profits-perdate-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
