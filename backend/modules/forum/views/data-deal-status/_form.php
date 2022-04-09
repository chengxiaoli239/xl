<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\DataDealStatus */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="data-deal-status-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'lottery_type')->textInput() ?>

                    <?= $form->field($model, 'qihao')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'next_qihao')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'status_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'static4dPerDateProfits_status')->textInput() ?>

                    <?= $form->field($model, 'static4dPerDateProfits_status_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'updateDs_status')->textInput() ?>

                    <?= $form->field($model, 'updateDs_status_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'updateDsYL_status')->textInput() ?>

                    <?= $form->field($model, 'updateDsYL_status_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'update3NumYL_status')->textInput() ?>

                    <?= $form->field($model, 'update3NumYL_status_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'updateSdHzYL_status')->textInput() ?>

                    <?= $form->field($model, 'updateSdHzYL_status_desc')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'opProfitsPlans_status')->textInput() ?>

                    <?= $form->field($model, 'opProfitsPlans_status_desc')->textInput(['maxlength' => true]) ?>

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
