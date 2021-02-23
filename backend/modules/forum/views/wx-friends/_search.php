<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\WxFriends */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="wx-friends-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'Uin') ?>

    <?= $form->field($model, 'UserName') ?>

    <?= $form->field($model, 'NickName') ?>

    <?= $form->field($model, 'HeadImgUrl') ?>

    <?php // echo $form->field($model, 'ContactFlag') ?>

    <?php // echo $form->field($model, 'MemberCount') ?>

    <?php // echo $form->field($model, 'MemberList') ?>

    <?php // echo $form->field($model, 'RemarkName') ?>

    <?php // echo $form->field($model, 'HideInputBarFlag') ?>

    <?php // echo $form->field($model, 'Sex') ?>

    <?php // echo $form->field($model, 'Signature') ?>

    <?php // echo $form->field($model, 'VerifyFlag') ?>

    <?php // echo $form->field($model, 'OwnerUin') ?>

    <?php // echo $form->field($model, 'PYInitial') ?>

    <?php // echo $form->field($model, 'PYQuanPin') ?>

    <?php // echo $form->field($model, 'RemarkPYInitial') ?>

    <?php // echo $form->field($model, 'RemarkPYQuanPin') ?>

    <?php // echo $form->field($model, 'StarFriend') ?>

    <?php // echo $form->field($model, 'AppAccountFlag') ?>

    <?php // echo $form->field($model, 'Statues') ?>

    <?php // echo $form->field($model, 'AttrStatus') ?>

    <?php // echo $form->field($model, 'Province') ?>

    <?php // echo $form->field($model, 'City') ?>

    <?php // echo $form->field($model, 'Alias') ?>

    <?php // echo $form->field($model, 'SnsFlag') ?>

    <?php // echo $form->field($model, 'UniFriend') ?>

    <?php // echo $form->field($model, 'DisplayName') ?>

    <?php // echo $form->field($model, 'ChatRoomId') ?>

    <?php // echo $form->field($model, 'KeyWord') ?>

    <?php // echo $form->field($model, 'EncryChatRoomId') ?>

    <?php // echo $form->field($model, 'IsOwner') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
