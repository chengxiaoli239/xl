<?php

use backend\service\SscDataService;
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
                <?= Html::encode($this->title).'[快选-三字定]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin([
                    //'fieldConfig'=>[ 'template'=> "{label}\n<div class=\"col-sm-8\">{input}</div>\n{error}", ]
                ]); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->
                <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">
                <div class="row">
                    <div class="col-lg-2 col-xs-6">
                        <?= $form->field($model, 'playway')->radioList([
                            //'1'=>'二字定',
                            '2'=>'三字定',
                            //'3'=>'四字定',
                        ])->label('类型') ?>
                    </div>
                    <div class="col-lg-2 col-xs-6">
                        <?= $form->field($model, 'status')->radioList([
                            '0'=>'关',
                            '1'=>'开',
                        ])->label('状态') ?>
                    </div>
                    <div class="col-lg-2 col-xs-6">
                        <?= $form->field($model, 'is_test')->radioList([
                            '0'=>'真',
                            '1'=>'模拟',
                        ])->label('真/模拟') ?>
                    </div>
                    <div class="col-lg-2 col-xs-6">
                        <?= $form->field($model, 'single')->textInput() ?>
                    </div>
                </div>
                <!--排除、上奖模板引入-->
                <?php include(dirname(__FILE__).'/arise_or_not.php');?>

                <!--配数表单引入-->
                <?php include(dirname(__FILE__).'/peishu_form.php'); ?>
                <!--定位合分模板引入-->
                <?php include(dirname(__FILE__).'/dw_hefen_form.php');?>

                <div class="row">
                    <div class="col-lg-2 col-xs-6">
                        <!--三定含除、取-->
                        <?= $form->field($model, 'arise_in_sel')->checkboxList([1=>'除',2=>'取'])->label('2.三字定含') ?>
                    </div>
                    <div class="col-lg-2 col-xs-6">
                        <?= $form->field($model, 'arise_in')->textInput()->label('2.三字定含')?>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                        <!--两数合-->
                        <?= $form->field($model, 'no_fix_hefen_pos_2')->checkboxList(
                            [1=>'两数'],
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
                        )->label('2.不定位合分') ?>
                    </div>
                    <div class="col-lg-2 col-xs-6">
                        <!--位置合分：合分-->
                        <?= $form->field($model, 'no_fix_hefen2')->textInput()->label('2.两数不定位合分:值')?>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                        <!--三数合-->
                        <?= $form->field($model, 'no_fix_hefen_pos_3')->checkboxList(
                            [2=>'三数'],
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
                        )->label('3.不定位合分') ?>
                    </div>
                    <div class="col-lg-2 col-xs-6">
                        <!--位置合分：合分-->
                        <?= $form->field($model, 'no_fix_hefen3')->textInput()->label('3.三数不定位合分:值')?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-2 col-xs-6"> <?= $form->field($model, 'p1')->textInput()->label('千') ?> </div>
                    <div class="col-lg-2 col-xs-6"> <?= $form->field($model, 'p2')->textInput()->label('百') ?> </div>
                    <div class="col-lg-2 col-xs-6"> <?= $form->field($model, 'p3')->textInput()->label('十') ?> </div>
                    <div class="col-lg-2 col-xs-6"> <?= $form->field($model, 'p4')->textInput()->label('个') ?> </div>
                </div>

                <div class="row">
                    <div class="col-lg-1 col-xs-4">
                    <?= $form->field($model, 'type_2')->checkBoxList([
                            0=>'除',
                            1=>'取'
                        ])->label('双重') ?>
                    </div>
                    <div class="col-lg-1 col-xs-4">
                        <?= $form->field($model, 'type_3')->checkBoxList([
                            0=>'除',
                            1=>'取'
                        ])->label('三重') ?>
                    </div>
                    <div class="col-lg-1 col-xs-4">
                        <?= $form->field($model, 'type_2b')->checkBoxList([
                            0=>'除',
                            1=>'取'
                        ])->label('两兄弟') ?>
                    </div>
                    <div class="col-lg-1 col-xs-4">
                        <?= $form->field($model, 'type_3b')->checkBoxList([
                            0=>'除',
                            1=>'取'
                        ])->label('三兄弟') ?>
                    </div>
                    <div class="col-lg-1 col-xs-4">
                        <?= $form->field($model, 'type_log')->checkBoxList([
                            //0=>'非四单四双',
                            0=>'除',
                            1=>'取',
                        ])->label('对数'); $form->field($model, 'stop_loss')->textInput($plan_types)->label('止损点(正数，例：4000)') ?>
                    </div>
                </div>

                <!--对数表单引入-->
                <?php include(dirname(__FILE__).'/log_form.php'); ?>

                <!--大小、单双模板引入-->
                <?php include(dirname(__FILE__).'/dx_ds.php');?>

                <!--分离数数表单引入-->
                <?php //include(dirname(__FILE__).'/fenli_shu_form.php'); ?>

                <!--动态过滤号码-->
                <?php include(dirname(__FILE__).'/filter_dynamic.php'); # 动态过滤号码 ?>
                <div class="row">
                    <div class="col-lg-12 col-xs-12">
                        <!--?= $form->field($model,"desc")->textarea([ 'autofocus' => false,'style'=>'height:60px' ])?-->
                        <?= $form->field($model,"base_codes")->label('导入号码(<span id="base_codes_nums">'.(empty($model->base_codes)?0:count(explode(',', $model->base_codes))).'</span>)')->textarea([ 'autofocus' => false,'style'=>'height:200px','id'=>'model-base_codes'])?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:1-3-7-15-31-62-125-251') ?>
                    </div>
                    <div class="col-lg-2 col-xs-6">
                        <?= $form->field($model, 'bet_while_miss')->textInput()->label('遗漏x期投,如:10') ?>
                    </div>
                </div>

                <!--区间盈利止盈止损-->
                <?php if(isset(SscDataService::PLAN_TYPE_OPTIONS[SscDataService::PLAN_TYPE_AREA_SINGLES_BET])){ include(dirname(__FILE__).'/take_profits_area.php');} ?>

                <!--止盈止损-->
                <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>
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
<!-- 布局已加载jQuery 2.0.3，无需重复加载 -->
<?php include(dirname(__FILE__).'/query-profits.php');?>
<script>
$(function () {
    // 动态过滤按钮事件已在 filter_dynamic.php 中统一处理
    $('.checkbox-item').click(function() {
        var name = $(this).attr('name');
        $('input[name="' + name + '"]').not(this).prop('checked', false);
    });
    $(".id-query").click(function () {
        url = '/forum/ssc-static-yl/query'
        data = $('#w0').serialize()+'&type='+$(this).data('type');
        $.post(url, data, function(rst) {
            $('#tip_msg_rst').html(
                '<strong>号码：</strong>' + rst.code_desc + "<br>"
                + '<strong>组数：</strong>' + rst.counts + "<br>"
                + '<strong>当前：</strong>' + rst.current_times + "<br>"
                + '<strong>本周：最大遗漏: </strong>'+ rst.week_max_miss + '次&nbsp;&nbsp;&nbsp;<strong>最大连中: </strong>'+ rst.week_max_hit + "次<br>"
                + '<strong>本月：最大遗漏: </strong>'+ rst.month_max_miss + '次&nbsp;&nbsp;&nbsp;<strong>最大连中: </strong>'+ rst.month_max_hit + "次<br>"
                + "<strong>遗漏记录：</strong>"  +rst.yl_str
            )
            $('#rstTipModal').modal('show');
            $('#codes_nums').html(rst.counts)
            $('#usersysplans-codes').html(rst.codeData);
        });
    });

});
</script>
