<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SscKjData */

$this->title = Yii::t('app', 'Update Ssc Kj Data: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Kj Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="ssc-kj-data-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
