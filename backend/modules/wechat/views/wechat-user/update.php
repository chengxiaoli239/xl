<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\WechatUser */

$this->title = 'Update Wechat User: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Wechat Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="wechat-user-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
