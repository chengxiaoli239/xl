<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\SysCustomizedFilters */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sys-customized-filters-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'type')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'playway')->textInput() ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'codes')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'sort')->textInput() ?>

                    <?= $form->field($model, 'desc')->textInput(['maxlength' => true]) ?>

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
