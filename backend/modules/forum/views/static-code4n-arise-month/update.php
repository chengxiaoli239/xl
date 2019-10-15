<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticCode4nAriseMonth */

$this->title = Yii::t('app', 'Update Static Code4n Arise Month: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Code4n Arise Months'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="static-code4n-arise-month-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
