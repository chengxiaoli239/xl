<?php

use yii\helpers\Html;
use izyue\admin\widgets\activeform;

/* @var $this yii\web\View */
/* @var $model backend\models\SscDwHzStatic */
/* @var $form izyue\admin\widgets\ActiveForm */
?>

<div class="ssc-dw-hz-static-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'positions')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'qihao')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'periods')->textInput() ?>

                    <?= $form->field($model, 'hz_0')->textInput() ?>

                    <?= $form->field($model, 'hz_1')->textInput() ?>

                    <?= $form->field($model, 'hz_2')->textInput() ?>

                    <?= $form->field($model, 'hz_3')->textInput() ?>

                    <?= $form->field($model, 'hz_4')->textInput() ?>

                    <?= $form->field($model, 'hz_5')->textInput() ?>

                    <?= $form->field($model, 'hz_6')->textInput() ?>

                    <?= $form->field($model, 'hz_7')->textInput() ?>

                    <?= $form->field($model, 'hz_8')->textInput() ?>

                    <?= $form->field($model, 'hz_9')->textInput() ?>

                    <?= $form->field($model, 'hz_10')->textInput() ?>

                    <?= $form->field($model, 'hz_11')->textInput() ?>

                    <?= $form->field($model, 'hz_12')->textInput() ?>

                    <?= $form->field($model, 'hz_13')->textInput() ?>

                    <?= $form->field($model, 'hz_14')->textInput() ?>

                    <?= $form->field($model, 'hz_15')->textInput() ?>

                    <?= $form->field($model, 'hz_16')->textInput() ?>

                    <?= $form->field($model, 'hz_17')->textInput() ?>

                    <?= $form->field($model, 'hz_18')->textInput() ?>

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
