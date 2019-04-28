<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SscDwHzStatic */

$this->title = Yii::t('app', 'Update Ssc Dw Hz Static: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dw Hz Statics'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="ssc-dw-hz-static-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
