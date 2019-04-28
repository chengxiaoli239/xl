<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Ssc3numYl */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc3num-yl-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'zhi')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'current_miss')->textInput() ?>

                    <?= $form->field($model, 'last_time_miss')->textInput() ?>

                    <?= $form->field($model, 'last_time_miss_range')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'max_miss')->textInput() ?>

                    <?= $form->field($model, 'max_range')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'yl_records')->textarea(['rows' => 6]) ?>

                    <?= $form->field($model, 'history_max_miss')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

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
