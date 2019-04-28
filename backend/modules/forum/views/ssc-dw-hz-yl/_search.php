<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SscDwHzYl */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc-dw-hz-yl-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <!--?= $form->field($model, 'id') ?-->

    <!--?= $form->field($model, 'positions') ?-->
    <?= $form->field($model, 'positions')->checkboxList(['1,2'=>'1,2', '1,3'=>'1,3', '1,4'=>'1,4', '2,3'=>'2,3', '2,4'=>'2,4', '3,4'=>'3,4']) ?>

    <?= $form->field($model, 'zhi')->checkboxList([6=>6, 7=>7, 8=>8, 9=>9, 10=>10, 11=>11, 12=>12]) ?>

    <!--?= $form->field($model, 'qihao') ?-->

    <!--?= $form->field($model, 'max_miss') ?-->

    <!--?php  echo $form->field($model, 'current_miss') ?-->

    <?php // echo $form->field($model, 'last_time_miss') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
