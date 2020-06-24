<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<style>
    .btn-default:active {
        background-color: green !important;
    }
</style>

<div class="user-sys-plans-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->
                    <input type="hidden" value="3" name="UserSysPlans[playway]">
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">

                    <div class="row">
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                //'2'=>'三字定',
                                '3'=>'四字定',
                                //'4'=>'一字定',
                            ])->label('类型') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'status')->radioList([
                                '0'=>'关闭',
                                '1'=>'开启',
                            ])->label('状态') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'is_test')->radioList([
                                '0'=>'真',
                                '1'=>'模拟',
                            ])->label('真/模拟') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-xs-12">
                            <?= $form->field($model, 'single')->textInput() ?>
                        </div>
                    </div>
                    <!--?= $form->field($model, 'tz_type')->textInput() ?-->
                    <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                    <?= $form->field($model, 'hz_Arr')->checkboxList($hzArr)->label('投注类型[单双] &nbsp;&nbsp;<a href="#" class="btn btn-xs btn-info reverse_type_4ds">反买</a>') ?>
                    <!--?= $form->field($model, 'tz_type')->radioList($kArr)->label('投注类型') ?-->
                <div class="row">
                    <div class="col-lg-2 col-xs-4">
                        <a id="2d2s" href="#" class="btn btn-default btn-xs">两单两双</a>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                        <a id="1d3s" href="#" class="btn btn-default btn-xs">一单三双</a>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                        <a id="1s3d" href="#" class="btn btn-default btn-xs">一双三单</a>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                        <a id="1d3s4d4s" href="#" class="btn btn-default btn-xs">一单三双44</a>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                        <a id="1s3d4d4s" href="#" class="btn btn-default btn-xs">一双三单44</a>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                        <a id="4d4s" href="#" class="btn btn-default btn-xs">四单四双</a>
                    </div>
                </div>

                <!--?= $form->field($model, 'buy_type')->textInput() ?-->

                    <?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:0.1-0.3-0.7-1.5-3.1') ?>

                    <!--止盈止损-->
                    <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                    <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>

                    <!--?= $form->field($model, 'nums')->textInput() ?-->

                    <!--?= $form->field($model, 'created_at')->textInput() ?-->

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

                    <!--?= $form->field($model, 'update_time')->textInput() ?-->

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
<input type="hidden" id="type_id" value="">
<input type="hidden" id="type_val" value="">
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
$(function () {
    // 两单两双
    $('#2d2s').click(function () {
        $("[name='UserSysPlans[hz_Arr][]']").each(function () {
            var arr = ['1122', '1212', '2121', '2112', '2211', '1221'];
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }else if($(this).prop('checked') == true) {
                $(this).prop("checked", false);
            }
        });
    });
    // 一单三双
    $('#1d3s').click(function () {
        $("[name='UserSysPlans[hz_Arr][]']").each(function () {
            var arr = ['1222', '2122', '2212', '2221'];
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }else if($(this).prop('checked') == true) {
                $(this).prop("checked", false);
            }
        });
    });
    // 一单三双四单四双
    $('#1d3s4d4s').click(function () {
        //type_id = $('#type_id').val('1d3s4d4s');
        type_id = $('#type_id').val();
        if(type_id == '1d3s4d4s' && $('#type_val').val() == 1){
            $("[name='UserSysPlans[hz_Arr][]']").each(function () {
                $(this).prop("checked",false);
            });
            $('#type_val').val(0);
        }else if(type_id == '1d3s4d4s' && $('#type_val').val() == 0){
            $('#type_val').val(1);
        }else if(type_id != '1d3s4d4s' || $('#type_val').val() == 0){
            $('#type_id').val('1d3s4d4s');
            $('#type_val').val(1);
        }
        val = $('#type_val').val();
        $("[name='UserSysPlans[hz_Arr][]']").each(function () {
            var arr = ['1222', '2122', '2212', '2221', '1111', '2222'];
            v = $.inArray($(this).val(), arr);
            if(v >= 0 && val == 1){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked", false);
            }
        });
    });
    // 一双三单
    $('#1s3d').click(function () {
        $("[name='UserSysPlans[hz_Arr][]']").each(function () {
            var arr = ['2111', '1211', '1121', '1112'];
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }else if($(this).prop('checked') == true) {
                $(this).prop("checked", false);
            }
        });
    });
    // 一双三单四单四双
    $('#1s3d4d4s').click(function () {
        type_id = $('#type_id').val();
        if(type_id == '1s3d4d4s' && $('#type_val').val() == 1){
            $("[name='UserSysPlans[hz_Arr][]']").each(function () {
                $(this).prop("checked",false);
            });
            $('#type_val').val(0);
        }else if(type_id == '1s3d4d4s' && $('#type_val').val() == 0){
            $('#type_val').val(1);
        }else if(type_id != '1s3d4d4s'){
            $('#type_id').val('1s3d4d4s');
            $('#type_val').val(1);
        }
        val = $('#type_val').val();
        $("[name='UserSysPlans[hz_Arr][]']").each(function () {
            var arr = ['2111', '1211', '1121', '1112', '1111', '2222'];
            v = $.inArray($(this).val(), arr);
            console.log(v, $(this).val(), $(this).prop('checked'))
            if(v >= 0 && val == 1){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked", false);
            }
        });
        //$('#type_val').val('');
    });
    // 四单四双
    $('#4d4s').click(function () {
        type_id = $('#type_id').val();
        if(type_id == '4d4s' && $('#type_val').val() == 1){
            $("[name='UserSysPlans[hz_Arr][]']").each(function () {
                $(this).prop("checked",false);
            });
            $('#type_val').val(0);
        }else if(type_id == '4d4s' && $('#type_val').val() == 0){
            $('#type_val').val(1);
        }else if(type_id != '4d4s' || $('#type_val').val() == 0){
            $('#type_id').val('4d4s');
            $('#type_val').val(1);
        }
        val = $('#type_val').val();
        $("[name='UserSysPlans[hz_Arr][]']").each(function () {
            var arr = ['1111', '2222'];
            v = $.inArray($(this).val(), arr);
            if(v >= 0 && val == 1){
                $(this).prop("checked",true);
            }else if($(this).prop('checked') == true) {
                $(this).prop("checked", false);
            }
        });
    });
    $('.reverse_type_4ds').click(function () {
        $("[name='UserSysPlans[hz_Arr][]']").each(function () {
            if($(this).prop('checked') == false){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked",false);
            }
        });
    });
});
</script>
