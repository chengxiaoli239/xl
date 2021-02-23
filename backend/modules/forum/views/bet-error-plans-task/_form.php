<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\BetErrorPlansTask */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bet-error-plans-task-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'codes')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'uid')->textInput() ?>

                    <?= $form->field($model, 'agent_id')->textInput() ?>

                    <?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'bet_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'bet_headers')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'post_datas')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'playway')->textInput() ?>

                    <?= $form->field($model, 'tz_type')->textInput() ?>

                    <?= $form->field($model, 'playway_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'bet_money')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'single')->textInput() ?>

                    <?= $form->field($model, 'qihao')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'kj_codes')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'sn')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'snid')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'plan_id')->textInput() ?>

                    <?= $form->field($model, 'tz_system_id')->textInput() ?>

                    <?= $form->field($model, 'lotteryclass')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'lottery_type')->textInput() ?>

                    <?= $form->field($model, 'post_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'error_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'updated_time')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

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
