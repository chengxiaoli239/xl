<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SportsRelated */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sports-related-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'uid') ?>

    <?= $form->field($model, 'relate_A_game_id') ?>

    <?= $form->field($model, 'relate_B_game_id') ?>

    <?= $form->field($model, 'relate_type') ?>

    <?php // echo $form->field($model, 'relate_sport_type') ?>

    <?php // echo $form->field($model, 'plate_A_id') ?>

    <?php // echo $form->field($model, 'plate_A_name') ?>

    <?php // echo $form->field($model, 'plate_B_id') ?>

    <?php // echo $form->field($model, 'plate_B_name') ?>

    <?php // echo $form->field($model, 'base_url_A') ?>

    <?php // echo $form->field($model, 'base_url_B') ?>

    <?php // echo $form->field($model, 'plate_bet_url_A') ?>

    <?php // echo $form->field($model, 'plate_bet_url_B') ?>

    <?php // echo $form->field($model, 'status') ?>

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
