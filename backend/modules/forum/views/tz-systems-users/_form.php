<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="tz-systems-users-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput() ?-->

                    <!--?= $form->field($model, 'tz_system_id')->textInput() ?-->

                    <!--?= $form->field($model, 'sys_name')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>

                    <!--?= $form->field($model, 'balance')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'status')->textInput() ?-->

                    <?= $form->field($model, 'ssc_domain')->textInput(['maxlength' => true]) ?>

                    <!--?= $form->field($model, 'cookie')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'created_at')->textInput() ?-->

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

                    <!--?= $form->field($model, 'update_time')->textInput() ?-->

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
