<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticPerHzPerdateProfits */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-per-hz-perdate-profits-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'date')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_1')->textInput() ?>

                    <?= $form->field($model, 'codes_2')->textInput() ?>

                    <?= $form->field($model, 'codes_3')->textInput() ?>

                    <?= $form->field($model, 'codes_4')->textInput() ?>

                    <?= $form->field($model, 'codes_5')->textInput() ?>

                    <?= $form->field($model, 'codes_6')->textInput() ?>

                    <?= $form->field($model, 'codes_7')->textInput() ?>

                    <?= $form->field($model, 'codes_8')->textInput() ?>

                    <?= $form->field($model, 'codes_9')->textInput() ?>

                    <?= $form->field($model, 'codes_10')->textInput() ?>

                    <?= $form->field($model, 'codes_11')->textInput() ?>

                    <?= $form->field($model, 'codes_12')->textInput() ?>

                    <?= $form->field($model, 'codes_13')->textInput() ?>

                    <?= $form->field($model, 'codes_14')->textInput() ?>

                    <?= $form->field($model, 'codes_15')->textInput() ?>

                    <?= $form->field($model, 'codes_16')->textInput() ?>

                    <?= $form->field($model, 'codes_17')->textInput() ?>

                    <?= $form->field($model, 'codes_18')->textInput() ?>

                    <?= $form->field($model, 'codes_19')->textInput() ?>

                    <?= $form->field($model, 'codes_20')->textInput() ?>

                    <?= $form->field($model, 'codes_21')->textInput() ?>

                    <?= $form->field($model, 'codes_22')->textInput() ?>

                    <?= $form->field($model, 'codes_23')->textInput() ?>

                    <?= $form->field($model, 'codes_24')->textInput() ?>

                    <?= $form->field($model, 'codes_25')->textInput() ?>

                    <?= $form->field($model, 'codes_26')->textInput() ?>

                    <?= $form->field($model, 'codes_27')->textInput() ?>

                    <?= $form->field($model, 'codes_28')->textInput() ?>

                    <?= $form->field($model, 'codes_29')->textInput() ?>

                    <?= $form->field($model, 'codes_30')->textInput() ?>

                    <?= $form->field($model, 'codes_31')->textInput() ?>

                    <?= $form->field($model, 'codes_32')->textInput() ?>

                    <?= $form->field($model, 'codes_33')->textInput() ?>

                    <?= $form->field($model, 'codes_34')->textInput() ?>

                    <?= $form->field($model, 'codes_35')->textInput() ?>

                    <?= $form->field($model, 'codes_36')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

                    <?= $form->field($model, 'update_time')->textInput() ?>

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
