<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\AgentUserBetLogs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="agent-user-bet-logs-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'access_token')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'member_id')->textInput() ?>

                    <?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'bet_logs')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'bet_codes')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'bet_codes_op')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'bet_type')->textInput() ?>

                    <?= $form->field($model, 'planway')->textInput() ?>

                    <?= $form->field($model, 'desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'lottery_type')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'qihao')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'tz_system_id')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

                    <?= $form->field($model, 'update_time')->textInput() ?>

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
