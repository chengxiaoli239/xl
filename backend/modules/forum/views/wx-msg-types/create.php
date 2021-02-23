<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\WxMsgTypes */

$this->title = Yii::t('app', 'Create Wx Msg Types');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Wx Msg Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="wx-msg-types-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
