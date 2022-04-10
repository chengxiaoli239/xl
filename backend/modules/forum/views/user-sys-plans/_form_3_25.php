<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<style>
    .form-control{
        padding: 0px 1px;
    }
</style>

<div class="user-sys-plans-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title).'[快选]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        //'inputOptions'=>['class'=>'p-1'],
                    ],
                ]); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">
                    <div class="row">
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                //'2'=>'三字定',
                                '3'=>'四字定',
                            ])->label('类型') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'status')->radioList([
                                '0'=>'关',
                                '1'=>'开',
                            ])->label('状态') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'is_test')->radioList([
                                '0'=>'真',
                                '1'=>'模拟',
                            ])->label('真/模拟') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'single')->textInput() ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">

                            <?= $form->field($model, 'arise')->textInput()->label('上奖') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'p1')->textInput()->label('千
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
                            ') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'p2')->textInput()->label('百
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
                            ') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'p3')->textInput()->label('十
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
                            ') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'p4')->textInput()->label('个
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
                                <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
                            ') ?>
                        </div>
                    </div>

                    <!--?= $form->field($model, 'tz_type')->textInput() ?-->
                    <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                    <!--?= $form->field($model, 'tz_type')->radioList($kArr)->label('投注类型') ?-->

                    <!--?= $form->field($model, 'buy_type')->textInput() ?-->

                    <!--?= $form->field($model, 'tz_sites')->textInput(['maxlength' => true]) ?-->

                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <!--配数1-->
                            <?= $form->field($model, 'ps_1')->textInput()->label('配数1')?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <!--配数2-->
                            <?= $form->field($model, 'ps_2')->textInput()->label('配数2')?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <!--三定含除、取-->
                            <?= $form->field($model, 'arise_in_sel')->checkboxList([1=>'除',2=>'取'])->label('3.四字定含') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'arise_in')->textInput()->label('3.四字定含')?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-xs-4">
                            <!--位置合分：位置-->
                            <?= $form->field($model, 'hefen_pos')->checkboxList($hefen_pos)->label('1.1定位合分:取') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <!--位置合分：合分-->
                            <?= $form->field($model, 'hefen')->textInput()->label('1.1定位合分:值')?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <!--两数合、三数合-->
                            <?= $form->field($model, 'no_fix_hefen_pos')->checkboxList([1=>'两数',2=>'三数'])->label('2.不定位合分') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <!--位置合分：合分-->
                            <?= $form->field($model, 'no_fix_hefen')->textInput()->label('2.不定位合分:值')?>
                        </div>
                    </div>
                    <!--位置合分：位置2-->
                    <!--?= $form->field($model, 'hefen_pos2')->checkboxList($hefen_pos)->label('1.2定位合分取:位置') ?-->
                    <!--位置合分：合分2-->
                    <!--?= $form->field($model, 'hefen2')->textInput()->label('1.2定位合分:值')?-->
                    <!--位置合分：位置3-->
                    <!--?= $form->field($model, 'hefen_pos3')->checkboxList($hefen_pos)->label('1.3定位合分取:位置') ?-->
                    <!--位置合分：合分3-->
                    <!--?= $form->field($model, 'hefen3')->textInput()->label('1.3定位合分:值')?-->
                    <!--位置合分：位置4-->
                    <!--?= $form->field($model, 'hefen_pos4')->checkboxList($hefen_pos)->label('1.4定位合分取:位置') ?-->
                    <!--位置合分：合分4-->
                    <!--?= $form->field($model, 'hefen4')->textInput()->label('1.4定位合分:值')?-->
                    <div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: green;">
                        <div class="col-lg-2 col-xs-2">
                            <label>任意位(不定)：</label>
                        </div>

                        <div class="col-lg-2 col-xs-2">
                            <?= $form->field($model, 'arb_pos_isbaohan')->checkboxList([1=>'是'])->label('包含') ?>
                        </div>
                        <div class="col-lg-4 col-xs-4">
                            <?= $form->field($model, 'arb_pos_codes')->textInput()->label('号码') ?>
                        </div>
                        <div class="col-lg-2 col-xs-3">
                            <?= $form->field($model, 'arb_pos_nums')->textInput()->label('个数') ?>
                        </div>
                    </div>


                    <?= $form->field($model, 'hz')->checkboxList($hzArr)
                        ->label('和值 
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_0_6">0-6</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_5_10">5-10</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_11_15">11-15</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_16_19">16-19</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_20_24">20-24</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_25_29">25-29</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_30_36">30-36</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_dan">单</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_shuang">双</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz">反买</a>
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_hz_Null">清</a>
                    ') ?>

                    <div class="row">
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_2')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('双重') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_3')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('三重') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_4')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('四重') ?>
                        </div>

                    <!--?= $form->field($model, 'hz_Arr')->textInput()->label('上奖号码(四个数字一组)，多组英文逗号隔开') ?-->

                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_22')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('双双重') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_2b')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('两兄弟') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_3b')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('三兄弟') ?>
                        </div>

                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_4b')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('四兄弟') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_log')->checkBoxList([
                                //0=>'非四单四双',
                                0=>'除',
                                1=>'取',
                            ])->label('对数') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_22b')->checkBoxList([
                                //0=>'非四单四双',
                                0=>'除',
                                1=>'取',
                            ])->label('双两兄') ?>
                        </div>
                    </div>
                    <!--
                    <div class="row">
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_4s')->checkBoxList([
                                //0=>'非四单四双',
                                0=>'除',
                                1=>'取',
                            ])->label('四双') ?>
                        </div>
                    </div>
                    -->

                <div class="row">
                    <div class="col-lg-4 col-xs-12">
                        <!--号码单双类型:两单两双，四单，四双-->
                    <?= $form->field($model, 'type_4ds')->checkboxList($type_4ds_Arr)->label('单双类型 &nbsp;&nbsp;<a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds">反买</a>') ?>
                    </div>
                    <div class="col-lg-8 col-xs-12">
                    <!--号码单双类型:1122,2121 等-->
                    <?= $form->field($model, 'type_ds_details')->checkboxList($type_ds_details_Arr)->label('单双类型 &nbsp;&nbsp;<a href="javascript:;" class="btn btn-xs btn-info reverse_type_ds_detail">反买</a>') ?>
                    </div>
                </div>


                    <div class="row">
                        <div class="col-lg-6 col-xs-12">
                            <?= $form->field($model, 'remove_types')->checkboxList($code_types)->label('类型除【并】') ?>
                        </div>
                        <div class="col-lg-3 col-xs-12">
                            <?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:1-3-7-15-31-62-125-251') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'bet_while_miss')->textInput()->label('遗漏x期投,如:10') ?>
                        </div>
                    </div>


                    <!--排除前xx期-->
                    <?php include(dirname(__FILE__).'/filter_xs_before.php'); # 功能完好，不常用先注释 ?>
                    <?php //include(dirname(__FILE__).'/A_x_arise_B_y_arise_bet_B.php'); # A出x次B出y次投B ?>

                    <!--区间盈利止盈止损-->
                    <?php include(dirname(__FILE__).'/take_profits_area.php'); ?>

                    <!--止盈止损-->
                    <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                    <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>

                    <!--?= $form->field($model, 'created_at')->textInput() ?-->

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

                    <!--?= $form->field($model, 'update_time')->textInput() ?-->

                    <div class="row">
                        <div class="col-lg-12 col-xs-12">
                            <!--?= $form->field($model,"desc")->textarea([ 'autofocus' => false,'style'=>'height:60px' ])?-->
                            <?= $form->field($model,"codes")->label('号码(<span id="codes_nums">0</span>)')->textarea([ 'autofocus' => false,'style'=>'height:200px' ])?>
                        </div>
                    </div>
                    <?php include(dirname(__FILE__).'/act-button.php');?>

                <input type="hidden" id="lottery_type" name="UserSysPlans[lottery_type]" value="<?=$lottery_type?>">
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
<div class="modal fade" id="rstTipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">提示信息</h4>
            </div>
            <div class="modal-body">
                <div class="form-group up-reason">
                    <label id="tip_msg_rst" for="tip_msg_rst"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="opRstConfirm">确定</button>
            </div>
        </div>
    </div>
</div>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<?php include(dirname(__FILE__).'/query-profits.php');?>
<script>
$(function () {
    // 四定单双：两单两双，四单，四双等
    $('.reverse_type_4ds').click(function () {
        $("[name='UserSysPlans[type_4ds][]']").each(function () {
            if($(this).prop('checked') == false){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked",false);
            }
        });
    });
    // 单双类型:1122,2121,2222 等16种组合
    $('.reverse_type_ds_detail').click(function () {
        $("[name='UserSysPlans[type_ds_details][]']").each(function () {
            if($(this).prop('checked') == false){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked",false);
            }
        });
    });

    // 和值反选
    $('.reverse_type_hz').click(function () {
        $("[name='UserSysPlans[hz][]']").each(function () {
            if($(this).prop('checked') == false){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked",false);
            }
        });
    });
    // 0-6
    $('.reverse_type_hz_0_6').click(function () {
        arr = ['0','1','2','3','4','5','6']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 5-10
    $('.reverse_type_hz_5_10').click(function () {
        arr = ['5','6','7','8','9','10']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 11-15
    $('.reverse_type_hz_11_15').click(function () {
        arr = ['11','12','13','14','15']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 16-19
    $('.reverse_type_hz_16_19').click(function () {
        arr = ['16','17','18','19']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 20-24
    $('.reverse_type_hz_20_24').click(function () {
        arr = ['20','21','22','23','24']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 25-29
    $('.reverse_type_hz_25_29').click(function () {
        arr = ['25','26','27','28','29']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 30-36
    $('.reverse_type_hz_30_36').click(function () {
        arr = ['30','31','32','33','34','35','36']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 单
    $('.reverse_type_hz_dan').click(function () {
        arr = ['1','3','5','7','9','11','13','15','17','19','21','23','25','27','29','31','33','35']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 双
    $('.reverse_type_hz_shuang').click(function () {
        arr = ['0','2','4','6','8','10','12','14','16','18','20','22','24','26','28','30','32','34','36']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    $('.reverse_type_hz_Null').click(function () {
        $("[name='UserSysPlans[hz][]']").each(function () {
            $(this).prop("checked",false);
        });
    });

    $(".id-query").click(function () {
        url = '/forum/ssc-static-yl/query'
        data = $('#w0').serialize()+'&type='+$(this).data('type');
        $.post(url, data, function(rst) {
            $('#tip_msg_rst').html('<strong>号码：</strong>'+rst.code_desc + "<br>" +'<strong>组数：</strong>'+ rst.counts + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
            $('#rstTipModal').modal('show');
            $('#codes_nums').html(rst.counts)
            $('#usersysplans-codes').html(rst.codeDatas);
        });
    });

    // 大
    $('.code_type_dx_1').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('56789') : obj.val('');
    });
    // 双
    $('.code_type_dx_2').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('01234') : obj.val('');
    });
    // 单
    $('.code_type_ds_1').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('13579') : obj.val('');
    });
    // 双
    $('.code_type_ds_2').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('02468') : obj.val('');
    });

});
</script>
