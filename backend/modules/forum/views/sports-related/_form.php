<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsRelated */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sports-related-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'relate_A_game_id')->textInput() ?>

                    <?= $form->field($model, 'relate_B_game_id')->textInput() ?>

                    <?= $form->field($model, 'relate_type')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'relate_sport_type')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'plate_A_id')->textInput() ?>

                    <?= $form->field($model, 'plate_A_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'plate_B_id')->textInput() ?>

                    <?= $form->field($model, 'plate_B_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'base_url_A')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'base_url_B')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'plate_bet_url_A')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'plate_bet_url_B')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'plate_bet_conditions')->textInput(['maxlength' => true]) ?>

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
