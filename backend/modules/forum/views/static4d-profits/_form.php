<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Static4dProfits */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static4d-profits-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'month')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_4d_all')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_13_31')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_22_22')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_1111_2222')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_13')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_31')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_13_2222')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_31_1111')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_31_2222')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_13_1111')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_31_2222_1111')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_13_1111_2222')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_2222')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_1111')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codes_1_nums')->textInput() ?>

                    <?= $form->field($model, 'codes_2_nums')->textInput() ?>

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
