<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\Bets */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bets-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'user_id')->textInput() ?>

                    <?= $form->field($model, 'wechat_user_id')->textInput() ?>

                    <?= $form->field($model, 'order_id')->textInput() ?>

                    <?= $form->field($model, 'play_method')->textInput() ?>

                    <?= $form->field($model, 'codes')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'bet_money')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'bonus')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'single')->textInput() ?>

                    <?= $form->field($model, 'ratio')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'profits')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'qihao')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'kj_codes')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'cancel_status')->textInput() ?>

                    <?= $form->field($model, 'is_simulate')->textInput() ?>

                    <?= $form->field($model, 'lottery_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'lottery_type')->textInput() ?>

                    <?= $form->field($model, 'is_profits_record')->textInput() ?>

                    <?= $form->field($model, 'bet_desc')->textarea(['rows' => 6]) ?>

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
