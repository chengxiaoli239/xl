<?php

use yii\helpers\Html;
use izyue\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\BettingRecords */
/* @var $form izyue\admin\widgets\ActiveForm */
?>

<div class="betting-records-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'codes')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'playway')->textInput() ?>
                    <?= $form->field($model, 'single')->textInput() ?>

                    <?= $form->field($model, 'playway_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'qihao')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'position')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'is_simulate')->textInput() ?>

                    <?= $form->field($model, 'lotteryclass')->textInput(['maxlength' => true]) ?>

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
