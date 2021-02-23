<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\SystemConfig */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="system-config-form row">
    <div class="col-lg-12">
        <section class="panel">
            <!--
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            -->
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                        <?= $form->field($model, 'key')->textInput(['maxlength' => true]) ?>
                    </div>
                </div>


                    <?= $form->field($model, 'value')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'extend')->textInput(['maxlength' => true]) ?>

                    <!--?= $form->field($model, 'created_at')->textInput() ?-->

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

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
