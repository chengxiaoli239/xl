<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\QueueLog */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="queue-log-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'id')->textInput() ?>

                    <?= $form->field($model, 'system_queue_id')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'business_id')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'params')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'remark')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'count')->textInput() ?>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'job_class')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'job_class_md5')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'time')->textInput() ?>

                    <?= $form->field($model, 'last_push_time')->textInput() ?>

                    <?= $form->field($model, 'complete_time')->textInput() ?>

                    <?= $form->field($model, 'delay')->textInput() ?>

                    <?= $form->field($model, 'type')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'create_time')->textInput() ?>

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
