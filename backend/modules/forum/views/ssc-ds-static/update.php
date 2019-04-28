<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SscDsStatic */

$this->title = Yii::t('app', 'Update Ssc Ds Static: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Ds Statics'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="ssc-ds-static-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
