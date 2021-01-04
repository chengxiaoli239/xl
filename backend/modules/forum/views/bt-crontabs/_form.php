<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\BtCrontabs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bt-crontabs-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'uid')->textInput() ?>

                    <?= $form->field($model, 'p_id')->textInput() ?>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'sName')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'sType')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'domain')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'echo')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'cycle')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'backupTo')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'save')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'where_minute')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'where_hour')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'where1')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'sBody')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'type_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'urladdress')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'addtime')->textInput() ?>

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
