<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\KjConfig */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="kj-config-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'host')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'api_host')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'path')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'is_batch')->textInput() ?>

                    <?= $form->field($model, 'lottery_type')->textInput() ?>

                    <?= $form->field($model, 'method')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'post_data')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'data_type')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'enable')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

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
