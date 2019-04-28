<?php

use yii\helpers\Html;
use backend\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\SscKjData */
/* @var $form backend\widgets\ActiveForm */
?>

<div class="ssc-kj-data-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'kj_code')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'code_str')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'code1')->textInput() ?>

                    <?= $form->field($model, 'code2')->textInput() ?>

                    <?= $form->field($model, 'code3')->textInput() ?>

                    <?= $form->field($model, 'code4')->textInput() ?>

                    <?= $form->field($model, 'code5')->textInput() ?>

                    <?= $form->field($model, 'code_1_2')->textInput() ?>

                    <?= $form->field($model, 'code_1_3')->textInput() ?>

                    <?= $form->field($model, 'code_1_4')->textInput() ?>

                    <?= $form->field($model, 'code_2_3')->textInput() ?>

                    <?= $form->field($model, 'code_2_4')->textInput() ?>

                    <?= $form->field($model, 'code_3_4')->textInput() ?>

                    <?= $form->field($model, 'qihao')->textInput() ?>

                    <?= $form->field($model, 'date')->textInput() ?>

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
