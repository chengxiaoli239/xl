<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */

//$this->title = Yii::t('app', 'Update Tz Systems Users: {nameAttribute}', [ 'nameAttribute' => $model->id, ]);
$this->title = '更新个人信息';
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tz Systems Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="user-view wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
