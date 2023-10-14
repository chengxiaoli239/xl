<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\WechatUser */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="wechat-user-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'user_id')->textInput() ?>

                    <?= $form->field($model, 'userName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'nickName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'aliasName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'balance')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'is_credit')->textInput() ?>

                    <?= $form->field($model, 'bigHead')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'smallHead')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'labelList')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'remark')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'expire_time')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

                    <?= $form->field($model, 'update_at')->textInput() ?>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <?= Html::submitButton('Save', ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
