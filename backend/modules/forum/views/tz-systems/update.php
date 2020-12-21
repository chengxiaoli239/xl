<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystems */

$this->title = Yii::t('app', 'Update Tz Systems: {nameAttribute}', [
    'nameAttribute' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tz Systems'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="tz-systems-update wrapper site-min-height">

    <?= $this->render('_form', [
        'allTzTypes' => $allTzTypes,
        'model' => $model,
    ]) ?>

</section>
