<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\StaticPerHzPerdateProfits */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-per-hz-perdate-profits-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'codes_1') ?>

    <?= $form->field($model, 'codes_2') ?>

    <?= $form->field($model, 'codes_3') ?>

    <?php // echo $form->field($model, 'codes_4') ?>

    <?php // echo $form->field($model, 'codes_5') ?>

    <?php // echo $form->field($model, 'codes_6') ?>

    <?php // echo $form->field($model, 'codes_7') ?>

    <?php // echo $form->field($model, 'codes_8') ?>

    <?php // echo $form->field($model, 'codes_9') ?>

    <?php // echo $form->field($model, 'codes_10') ?>

    <?php // echo $form->field($model, 'codes_11') ?>

    <?php // echo $form->field($model, 'codes_12') ?>

    <?php // echo $form->field($model, 'codes_13') ?>

    <?php // echo $form->field($model, 'codes_14') ?>

    <?php // echo $form->field($model, 'codes_15') ?>

    <?php // echo $form->field($model, 'codes_16') ?>

    <?php // echo $form->field($model, 'codes_17') ?>

    <?php // echo $form->field($model, 'codes_18') ?>

    <?php // echo $form->field($model, 'codes_19') ?>

    <?php // echo $form->field($model, 'codes_20') ?>

    <?php // echo $form->field($model, 'codes_21') ?>

    <?php // echo $form->field($model, 'codes_22') ?>

    <?php // echo $form->field($model, 'codes_23') ?>

    <?php // echo $form->field($model, 'codes_24') ?>

    <?php // echo $form->field($model, 'codes_25') ?>

    <?php // echo $form->field($model, 'codes_26') ?>

    <?php // echo $form->field($model, 'codes_27') ?>

    <?php // echo $form->field($model, 'codes_28') ?>

    <?php // echo $form->field($model, 'codes_29') ?>

    <?php // echo $form->field($model, 'codes_30') ?>

    <?php // echo $form->field($model, 'codes_31') ?>

    <?php // echo $form->field($model, 'codes_32') ?>

    <?php // echo $form->field($model, 'codes_33') ?>

    <?php // echo $form->field($model, 'codes_34') ?>

    <?php // echo $form->field($model, 'codes_35') ?>

    <?php // echo $form->field($model, 'codes_36') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
