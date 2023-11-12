<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystems */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="tz-systems-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'system_type_id')->textInput() ?>

                    <?= $form->field($model, 'ssc_domain')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->checkboxList(
                        [0=>'关闭', 1=>'开启'],
                        [
                            'item' => function ($index, $label, $name, $checked, $value) {
                                $options = ['value' => $value];
                                // Add your custom class here
                                $options['class'] = 'checkbox-item';
                                return Html::checkbox($name, $checked, $options) . Html::label($label, $name);
                            },
                        ]
                    )->label('开启状态') ?>

                    <?= $form->field($model, 'type')->checkboxList([1=>'时时彩', 2=>'网球', 3=>'3D'])->label('彩种类型') ?>

                    <?= $form->field($model, 'tz_types')->checkboxList($allTzTypes)->label('已对接玩法') ?>

                    <!--?= $form->field($model, 'created_at')->textInput() ?-->

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>

<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
$(function(){
    //$(":checkbox").click(function(){
    $("input[name='TzSystems[status][]']").click(function(){
        console.log($(this).parent().siblings().children()[0]);
        $(this).parent().siblings().children().each(function(n,ele){
            if($(this).is(":checked")){
                $(this).prop("checked",false)
            }else {
                $(this).prop("checked",true)
            }
        })
    });

    // 多选框变单选效果
    $('.checkbox-item').click(function() {
        var name = $(this).attr('name');
        $('input[name="' + name + '"]').not(this).prop('checked', false);
    });
});
</script>
