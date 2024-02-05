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
        <?php if(!empty($model->fenli_shu_sel)) foreach ($model->fenli_shu_sel as $index => $value): ?>
        <li class="li-row">
            <div class="col-lg-8 col-xs-12">
                <?= $form->field($model, 'fenli_shu_sel_'.$index)->checkboxList(
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
                <?= $form->field($model, "fenli_shu_code[$index]")->textInput(['maxlength' => true, 'num'=>$index])->label("分离数") ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="col-lg-2 col-xs-1">
        <div class="form-group field-fenli_shu-add">
            <label class="control-label"></label>
            <input type="hidden" name="UserSysPlans_fls_add" value="">
            <div id="UserSysPlans-fls-add">
                <label><?= \yii\helpers\Html::button('+', ['type'=>'button', 'class'=>'btn btn-xs btn-success', 'id'=>'filter-fls-add']) ?></label>
                <label><?= \yii\helpers\Html::button('-', ['type'=>'button', 'class'=>'btn btn-xs btn-danger filter-fls-delete']) ?></label>
            </div>
            <div class="help-block"></div>
        </div>
    </div>
</div>

<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
    $(document).ready(function() {
        //var index = 1; // 初始化索引为0，表示第一行
        var index = $(".li-row").length;

        $("#filter-fls-add").on('click', function() {
            console.log('add');
            if(index>=10){
                alert('最多添加10个')
                return ;
            }

            // 复制第一个 li 元素
            var $li = $("#filter-fenli_shu-ul li:first").clone(true);

            // 修改复制的元素中的 input 的 name
            $li.find(':input').each(function() {
                var name = $(this).attr('name');
                // 使用正则表达式将 _\d 替换为 _index
                var newName = name.replace(/_\d+/, '_' + index); // fenli_shu_sel_x
                $(this).attr('name', newName);
                $(this).attr('checked',false);
            });

            $li.find(':input').each(function() {
                var name = $(this).attr('name');
                // 使用正则表达式将 [0] 替换为 [index]
                var newName = name.replace(/\[\d+\]/, '[' + index + ']'); // fenli_shu_code
                $(this).attr('name', newName);
                //$(this).attr('value', '');
                // 清空 input 值
                if (name.includes('fenli_shu_code')) {
                    $(this).val('');
                }
            });

            // 将修改后的行追加到 ul 元素中
            $("#filter-fenli_shu-ul").append($li);

            // 自增索引
            index++;
        });

        $('.filter-fls-delete').on('click', function (){
            if ($("#filter-fenli_shu-ul li").length > 1) {
                // 删除最后一个 li 元素
                $("#filter-fenli_shu-ul li:last").remove();
            }
        })
    });
</script>