<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\SscKjData3num */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc-kj-data3num-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'code_str')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'code_3n')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'qihao')->textInput() ?>

                    <?= $form->field($model, 'date')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'update_time')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

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
