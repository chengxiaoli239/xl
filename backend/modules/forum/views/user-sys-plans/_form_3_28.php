<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-sys-plans-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title).'[系统快捷]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">
                    <input type="hidden" value="<?=$lottery_type?>" name="UserSysPlans[lottery_type]">
                    <div class="row">
                        <div class="col-lg-4 col-xs-4">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                //'2'=>'三字定',
                                '3'=>'四字定',
                            ])->label('类型') ?>
                        </div>
                        <div class="col-lg-4 col-xs-4">
                            <!--?= $form->field($model, 'status')->textInput() ?-->
                            <?= $form->field($model, 'status')->radioList([
                                '0'=>'关',
                                '1'=>'开',
                            ])->label('状态') ?>
                        </div>
                        <div class="col-lg-4 col-xs-4">
                            <?= $form->field($model, 'is_test')->radioList([
                                '0'=>'真',
                                '1'=>'模拟',
                            ])->label('真/模拟') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 col-xs-6">
                            <?= $form->field($model, 'single')->textInput() ?>
                        </div>
                        <div class="col-lg-4 col-xs-6">
                            <!--位置合分：合分-->
                        <?= $form->field($model, 'xhefen')->textInput()->label('合分值')?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-xs-6">
                            <?= $form->field($model, 'get_types')->checkboxList($code_types)->label('类型取【或】') ?>
                        </div>
                        <div class="col-lg-6 col-xs-6">
                            <?= $form->field($model, 'remove_types')->checkboxList($code_types)->label('类型除【并】') ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'get_hzs')->checkboxList($hzArr)->label('和值【取】 
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
                    <!--?= $form->field($model, 'remove_hzs')->checkboxList($hzArr)->label('和值【除】
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_0_6">0-6</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_5_10">5-10</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_11_15">11-15</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_16_19">16-19</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_20_24">20-24</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_25_29">25-29</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_30_36">30-36</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_dan">单</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_shuang">双</a>&nbsp;&nbsp;
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz">反买</a>
                        <a href="javascript:;" class="btn btn-xs btn-info fan reverse_type_hz_Null">清</a>
                    ') ?-->

                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'get_arises')->textInput()->label('上奖【取】') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'remove_arises')->textInput()->label('上奖【除】') ?>
                        </div>
                    </div>

                    <!--止盈止损-->
                    <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>
                    <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>

                    <!--div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div-->
                    <?php include(dirname(__FILE__).'/act-button.php');?>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
<div class="modal fade" id="rstTipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
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
<!--script src="/statics/js/ds_select.js"></script-->
<script>
    $(function () {
        $(".id-query").click(function () {
            url = '/forum/ssc-static-yl/query'
            data = $('#w0').serialize()+'&type='+$(this).data('type');
            $.post(url, data, function(rst) {
                $('#tip_msg_rst').html('<strong>号码：</strong>'+rst.code_desc + "<br>" +'<strong>组数：</strong>'+ rst.counts + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
                $('#rstTipModal').modal('show');
            });
        });

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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            });
        });
        // 0-6 取
        $('.reverse_type_hz_0_6').click(function () {
            arr = ['0','1','2','3','4','5','6']
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
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
            ob = $(this).hasClass('fan') ? 'remove_hzs' : 'get_hzs';
            console.log($(this).hasClass('fan'))
            $("[name='UserSysPlans["+ob+"][]']").each(function () {
                $(this).prop("checked",false);
            });
        });

        $(".id-query").click(function () {
            url = '/forum/ssc-static-yl/query'
            data = $('#w0').serialize()+'&type='+$(this).data('type');
            $.post(url, data, function(rst) {
                $('#tip_msg_rst').html('<strong>号码：</strong>'+rst.code_desc + "<br>" +'<strong>组数：</strong>'+ rst.counts + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
                $('#rstTipModal').modal('show');
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
