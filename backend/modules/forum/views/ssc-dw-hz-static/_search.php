<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SscDwHzStatic */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc-dw-hz-static-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'positions') ?>

    <?= $form->field($model, 'qihao') ?>

    <?= $form->field($model, 'periods') ?>

    <?= $form->field($model, 'hz_0') ?>

    <?php // echo $form->field($model, 'hz_1') ?>

    <?php // echo $form->field($model, 'hz_2') ?>

    <?php // echo $form->field($model, 'hz_3') ?>

    <?php // echo $form->field($model, 'hz_4') ?>

    <?php // echo $form->field($model, 'hz_5') ?>

    <?php // echo $form->field($model, 'hz_6') ?>

    <?php // echo $form->field($model, 'hz_7') ?>

    <?php // echo $form->field($model, 'hz_8') ?>

    <?php // echo $form->field($model, 'hz_9') ?>

    <?php // echo $form->field($model, 'hz_10') ?>

    <?php // echo $form->field($model, 'hz_11') ?>

    <?php // echo $form->field($model, 'hz_12') ?>

    <?php // echo $form->field($model, 'hz_13') ?>

    <?php // echo $form->field($model, 'hz_14') ?>

    <?php // echo $form->field($model, 'hz_15') ?>

    <?php // echo $form->field($model, 'hz_16') ?>

    <?php // echo $form->field($model, 'hz_17') ?>

    <?php // echo $form->field($model, 'hz_18') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
