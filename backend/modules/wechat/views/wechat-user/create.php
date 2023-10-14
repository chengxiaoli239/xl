<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\wechat\WechatUser */

$this->title = 'Create Wechat User';
$this->params['breadcrumbs'][] = ['label' => 'Wechat Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="wechat-user-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
