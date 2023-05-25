<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<!--大小、单双模板-->
<div class="row">
    <div class="col-lg-1 col-xs-6">
        <!--位置筛选：单-->
        <?= $form->field($model, 'odd_sel')->checkboxList(
            [1=>'除', 2=>'取'],
            [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('单') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置-->
        <?= $form->field($model, 'odd_pos')->checkboxList($sel_pos)->label('单:位置') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置筛选：双-->
        <?= $form->field($model, 'even_sel')->checkboxList(
            [1=>'除', 2=>'取'],
            [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('双') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置-->
        <?= $form->field($model, 'even_pos')->checkboxList($sel_pos)->label('双:位置') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置筛选：大-->
        <?= $form->field($model, 'big_sel')->checkboxList(
            [1=>'除', 2=>'取'],
            [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('大') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置-->
        <?= $form->field($model, 'big_pos')->checkboxList($sel_pos)->label('单:位置') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置筛选：小-->
        <?= $form->field($model, 'small_sel')->checkboxList(
            [1=>'除', 2=>'取'],
            [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('小') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置-->
        <?= $form->field($model, 'small_pos')->checkboxList($sel_pos)->label('双:位置') ?>
    </div>
</div>