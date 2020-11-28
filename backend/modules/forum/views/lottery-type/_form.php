<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\LotteryType */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="lottery-type-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'lottery_type')->textInput() ?>

                    <?= $form->field($model, 'enable')->textInput() ?>

                    <?= $form->field($model, 'isDelete')->textInput() ?>

                    <?= $form->field($model, 'sort')->textInput() ?>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'codeList')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'shortName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'info')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'onGetNoed')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'data_ftime')->textInput() ?>

                    <?= $form->field($model, 'defaultViewGroup')->textInput() ?>

                    <?= $form->field($model, 'android')->textInput() ?>

                    <?= $form->field($model, 'num')->textInput() ?>

                    <?= $form->field($model, 'typeGroupName')->textInput(['maxlength' => true]) ?>

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
