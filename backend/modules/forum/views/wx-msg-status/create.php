<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\WxMsgStatus */

$this->title = Yii::t('app', 'Create Wx Msg Status');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Wx Msg Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="wx-msg-status-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
