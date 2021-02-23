<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'admin_id')->textInput() ?-->

                    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'balance')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'simulate_balance')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

                    <!--?= $form->field($model, 'tz_password')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'cookie')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'pay_time')->textInput() ?>

                    <?= $form->field($model, 'desc')->textInput(['maxlength' => true]) ?>

                    <!--?= $form->field($model, 'status')->textInput() ?-->

                    <!--?= $form->field($model, 'created_at')->textInput() ?-->

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

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
