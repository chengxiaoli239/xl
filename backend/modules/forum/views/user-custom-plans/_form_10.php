<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserCustomPlans */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-custom-plans-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'hezhis')->textInput() ?>

                    <?= $form->field($model, 'positions')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'single')->textInput() ?>

                    <?= $form->field($model, 'is_simulate')->textInput() ?>

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
