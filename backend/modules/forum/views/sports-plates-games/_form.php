<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsPlatesGames */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sports-plates-games-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'plate_id')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'plate_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'bet_url')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'league_matches_id')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'league_matches_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'name1')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'name1_path')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'name2')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'name2_path')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'event_id')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'game_schedule')->textInput(['maxlength' => true]) ?>

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
