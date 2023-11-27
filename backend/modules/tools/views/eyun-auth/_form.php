<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuth */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="eyun-auth-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'type')->textInput() ?>

                    <?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'authorization')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'callback_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'base_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'desc')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

                    <?= $form->field($model, 'update_at')->textInput() ?>

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
