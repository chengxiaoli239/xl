<?php

# 对数表单
use yii\helpers\Html;
use yii\widgets\ActiveForm;
/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="row" style="border-width:1px;margin-top:3px;border-style:solid;border-color: red;">
    <div class="col-lg-1 col-xs-12">
        <?= $form->field($model, 'log_sel')->checkboxList(
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
        )->label('对数') ?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <!--对数1-->
        <?= $form->field($model, 'log_1')->textInput()->label('对数1')?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <!--对数2-->
        <?= $form->field($model, 'log_2')->textInput()->label('对数2')?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <!--对数3-->
        <?= $form->field($model, 'log_3')->textInput()->label('对数3')?>
    </div>

</div>
