<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticCodeTypeProfitsPerdate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-code-type-profits-perdate-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'date')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_2')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_3')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_22')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_2b')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_3b')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_4b')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_2_type_2b')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_2_type_3b')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_3n_2b')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'lottery_type')->textInput() ?>

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
