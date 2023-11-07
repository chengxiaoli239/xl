<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\statics\Static3dUserProfitsDayAll */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static3d-user-profits-day-all-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'date')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'user_id')->textInput() ?>

                    <?= $form->field($model, 'wechat_user_id')->textInput() ?>

                    <?= $form->field($model, 'bet_money')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'bonus')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'profits')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'lottery_type')->textInput() ?>

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
