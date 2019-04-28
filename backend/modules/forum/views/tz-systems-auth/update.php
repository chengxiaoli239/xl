<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsAuth */

$this->title = Yii::t('app', 'Update Tz Systems Auth: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tz Systems Auths'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="tz-systems-auth-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
