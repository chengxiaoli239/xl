<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsPlates */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sports-plates-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'plate_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'base_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'football_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'tennis_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'basketball_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'desc')->textarea(['rows' => 6]) ?>

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
