<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\EventsLiveDatas */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="events-live-datas-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'uid') ?>

    <?= $form->field($model, 'event_id') ?>

    <?= $form->field($model, 'clock_minute') ?>

    <?= $form->field($model, 'clock_second') ?>

    <?php // echo $form->field($model, 'clock_minutesLeftInPeriod') ?>

    <?php // echo $form->field($model, 'clock_secondsLeftInMinute') ?>

    <?php // echo $form->field($model, 'clock_period') ?>

    <?php // echo $form->field($model, 'clock_running') ?>

    <?php // echo $form->field($model, 'score_home') ?>

    <?php // echo $form->field($model, 'score_away') ?>

    <?php // echo $form->field($model, 'score_info') ?>

    <?php // echo $form->field($model, 'score_who') ?>

    <?php // echo $form->field($model, 'statics_football_home_yellowCards') ?>

    <?php // echo $form->field($model, 'statics_football_way_yellowCards') ?>

    <?php // echo $form->field($model, 'statics_football_home_redCards') ?>

    <?php // echo $form->field($model, 'statics_football_way_redCards') ?>

    <?php // echo $form->field($model, 'statics_football_home_corners') ?>

    <?php // echo $form->field($model, 'statics_football_way_corners') ?>

    <?php // echo $form->field($model, 'liveStatistics') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
