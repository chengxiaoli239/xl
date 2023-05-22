<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="row">
    <div class="col-lg-1 col-xs-6">
        <!--?= $form->field($model, 'ps_sel')->checkboxList([1=>'除',2=>'取'])->label('配数全转') ?-->
        <?= $form->field($model, 'ps_sel')->checkboxList(
            [1=>'除', 2=>'取'],
            [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        #'class' => 'ps_sel',
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('配数全转') ?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <!--配数1-->
        <?= $form->field($model, 'ps_1')->textInput()->label('配数1')?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <!--配数2-->
        <?= $form->field($model, 'ps_2')->textInput()->label('配数2')?>
    </div>
    <?php if($model->playway)?>
    <div class="col-lg-2 col-xs-6">
        <!--配数3-->
        <?= $form->field($model, 'ps_3')->textInput()->label('配数3')?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <!--配数4-->
        <?= $form->field($model, 'ps_4')->textInput()->label('配数4')?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'fixed_sel_pos')->checkboxList($sel_pos)->label('定位置');?>
    </div>
</div>
