<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SportsInPlay */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sports-in-play-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'event_id') ?>

    <?= $form->field($model, 'play_type') ?>

    <?= $form->field($model, 'game_court') ?>

    <?= $form->field($model, 'plate_id') ?>

    <?php // echo $form->field($model, 'home_name') ?>

    <?php // echo $form->field($model, 'away_name') ?>

    <?php // echo $form->field($model, 'home_score') ?>

    <?php // echo $form->field($model, 'away_score') ?>

    <?php // echo $form->field($model, 'plate_1X2_odds_1') ?>

    <?php // echo $form->field($model, 'plate_1X2_odds_2') ?>

    <?php // echo $form->field($model, 'plate_1X2_odds_3') ?>

    <?php // echo $form->field($model, 'plate_rolling_home') ?>

    <?php // echo $form->field($model, 'plate_rolling_away') ?>

    <?php // echo $form->field($model, 'bet_url') ?>

    <?php // echo $form->field($model, 'plate_bet_conditions') ?>

    <?php // echo $form->field($model, 'desc') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
