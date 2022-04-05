<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\EventsLiveDatas */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="events-live-datas-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'uid')->textInput() ?>

                    <?= $form->field($model, 'event_id')->textInput() ?>

                    <?= $form->field($model, 'clock_minute')->textInput() ?>

                    <?= $form->field($model, 'clock_second')->textInput() ?>

                    <?= $form->field($model, 'clock_minutesLeftInPeriod')->textInput() ?>

                    <?= $form->field($model, 'clock_secondsLeftInMinute')->textInput() ?>

                    <?= $form->field($model, 'clock_period')->textInput() ?>

                    <?= $form->field($model, 'clock_running')->textInput() ?>

                    <?= $form->field($model, 'score_home')->textInput() ?>

                    <?= $form->field($model, 'score_away')->textInput() ?>

                    <?= $form->field($model, 'score_info')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'score_who')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'statics_football_home_yellowCards')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'statics_football_way_yellowCards')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'statics_football_home_redCards')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'statics_football_way_redCards')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'statics_football_home_corners')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'statics_football_way_corners')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'liveStatistics')->textarea(['rows' => 6]) ?>

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
