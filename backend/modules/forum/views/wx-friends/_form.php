<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\WxFriends */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="wx-friends-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'Uin')->textInput() ?>

                    <?= $form->field($model, 'UserName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'NickName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'HeadImgUrl')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'ContactFlag')->textInput() ?>

                    <?= $form->field($model, 'MemberCount')->textInput() ?>

                    <?= $form->field($model, 'MemberList')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'RemarkName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'HideInputBarFlag')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'Sex')->textInput() ?>

                    <?= $form->field($model, 'Signature')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'VerifyFlag')->textInput() ?>

                    <?= $form->field($model, 'OwnerUin')->textInput() ?>

                    <?= $form->field($model, 'PYInitial')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'PYQuanPin')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'RemarkPYInitial')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'RemarkPYQuanPin')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'StarFriend')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'AppAccountFlag')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'Statues')->textInput() ?>

                    <?= $form->field($model, 'AttrStatus')->textInput() ?>

                    <?= $form->field($model, 'Province')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'City')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'Alias')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'SnsFlag')->textInput() ?>

                    <?= $form->field($model, 'UniFriend')->textInput() ?>

                    <?= $form->field($model, 'DisplayName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'ChatRoomId')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'KeyWord')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'EncryChatRoomId')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'IsOwner')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
