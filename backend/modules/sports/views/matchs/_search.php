<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\Matchs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="matchs-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'system_id') ?>

    <?= $form->field($model, 'game_type') ?>

    <?= $form->field($model, 'game_type_name') ?>

    <?= $form->field($model, 'g_id') ?>

    <?php // echo $form->field($model, 'game_id') ?>

    <?php // echo $form->field($model, 'game_name') ?>

    <?php // echo $form->field($model, 'player_1') ?>

    <?php // echo $form->field($model, 'player_2') ?>

    <?php // echo $form->field($model, 'player_1_water') ?>

    <?php // echo $form->field($model, 'player_2_water') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'is_bind') ?>

    <?php // echo $form->field($model, 'bind_id') ?>

    <?php // echo $form->field($model, 'type') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
