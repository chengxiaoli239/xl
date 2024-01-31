<?php

use common\service\ssc\filterCode\FenLiShu;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="row" style="border-width:1px;margin-top:3px;border-style:solid;border-color: red;">
    <ul id="filter-fenli_shu-ul">
        <li>
            <div class="col-lg-8 col-xs-12">
                <?= $form->field($model, 'fenli_shu_sel')->checkboxList(
                    FenLiShu::getTypeOptions($model->playway),
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
                )->label('分离类型') ?>
            </div>
            <div class="col-lg-2 col-xs-6">
                <!--分离数-->
                <?php foreach ($model->fenli_shu_code as $index => $code): ?>
                    <?= $form->field($model, "fenli_shu_code[$index]")->textInput(['maxlength' => true])->label("分离数") ?>
                <?php endforeach; ?>
            </div>
        </li>
    </ul>

    <!--
    <div class="col-lg-2 col-xs-1">
        <div class="form-group field-fenli_shu-add">
            <label class="control-label"></label>
            <input type="hidden" name="UserSysPlans_fls_add" value="">
            <div id="UserSysPlans-fls-add">
                <label><?= \yii\helpers\Html::button('+', ['type'=>'button', 'class'=>'btn btn-xs btn-success', 'id'=>'filter-fls-add']) ?></label>
            </div>
            <div class="help-block"></div>
        </div>
    </div>
    -->
</div>

o<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
    $("#filter-fls-add").click(function(){
        console.log('add')
        var $li = $("#filter-fenli_shu-ul li:first").clone(true);
        $($li).appendTo("#filter-fenli_shu-ul");
    })
</script>