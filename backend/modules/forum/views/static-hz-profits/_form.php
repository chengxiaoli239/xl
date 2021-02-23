<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticHzProfits */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-hz-profits-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'month')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'hz_0_4')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'hz_5_10')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'hz_11_15')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'hz_16_19')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'hz_20_24')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'hz_25_29')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'hz_30_35')->textInput(['maxlength' => true]) ?>

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
