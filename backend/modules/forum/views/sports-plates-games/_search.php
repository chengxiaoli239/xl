<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SportsPlatesGames */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sports-plates-games-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'plate_id') ?>

    <?= $form->field($model, 'plate_name') ?>

    <?= $form->field($model, 'bet_url') ?>

    <?= $form->field($model, 'league_matches_id') ?>

    <?php // echo $form->field($model, 'league_matches_name') ?>

    <?php // echo $form->field($model, 'name1') ?>

    <?php // echo $form->field($model, 'name1_path') ?>

    <?php // echo $form->field($model, 'name2') ?>

    <?php // echo $form->field($model, 'name2_path') ?>

    <?php // echo $form->field($model, 'event_id') ?>

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
