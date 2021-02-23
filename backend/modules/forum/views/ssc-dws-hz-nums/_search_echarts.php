<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SscDwsHzNums */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc-dws-hz-nums-search">

    <?php $form = ActiveForm::begin([
        'action' => ['echarts'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <!--?= $form->field($model, 'id') ?-->

    <!--?= $form->field($model, 'hezhi') ?-->

    <!--?= $form->field($model, 'positions') ?-->

    <!--?= $form->field($model, 'periods') ?-->
    <?= $form->field($model, 'positions')->radioList([
            '1,2'=>'1,2', '1,3'=>'1,3', '1,4'=>'1,4', '2,3'=>'2,3', '2,4'=>'2,4', '3,4'=>'3,4'
    ]) ?>
    <?= $form->field($model, 'hezhi')->radioList([
            6=>6, 7=>7, 8=>8, 9=>9, 10=>10, 11=>11, 12=>12
    ]) ?>
    <!--?= $form->field($model, 'periods')->checkboxList([
            20=>20, 50=>50, 100=>100, 120=>120, 200=>200, 300=>300, 500=>500, 1000=>1000,2000=>2000,5000=>5000
    ]) ?-->

    <!--?= $form->field($model, 'qihao') ?-->

    <?php // echo $form->field($model, 'nums') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
