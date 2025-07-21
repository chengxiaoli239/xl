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
                <?= Html::encode($this->title).'[快选-二字定]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">
                <div class="row">
                    <div class="col-lg-2 col-xs-4">
                        <?= $form->field($model, 'playway')->radioList([
                        '1'=>'二字定',
                        //'2'=>'三字定',
                        //'3'=>'四字定',
                    ])->label('投注方式') ?>
                    </div>
                    <div class="col-lg-2 col-xs-4">
                    <!--?= $form->field($model, 'status')->textInput() ?-->
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'关闭',
                        '1'=>'开启',
                    ])->label('投注状态') ?>
                    </div>
                    <div class="col-lg-2 col-xs-4">
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

                <!--固定位置模板引入-->
                <?php include(dirname(__FILE__).'/fixed_positions.php');?>

                <!--配数表单引入-->
                <?php include(dirname(__FILE__).'/peishu_form.php'); ?>

                <!--定位合分模板引入-->
                <?php include(dirname(__FILE__).'/dw_hefen_form.php');?>

                <div class="row">
                    <div class="col-lg-3 col-xs-4">
                        <!--?= $form->field($model, 'hz_Arr')->textInput()->label('上奖号码(四个数字一组)，多组英文逗号隔开') ?-->
                    <?= $form->field($model, 'type_2')->checkBoxList([
                        0=>'除',
                        1=>'取'
                    ])->label('双重') ?>
                    </div>
                    <div class="col-lg-3 col-xs-4">
                    <?= $form->field($model, 'type_2b')->checkBoxList([
                        0=>'除',
                        1=>'取'
                    ])->label('两兄弟') ?>
                    </div>
                    <div class="col-lg-3 col-xs-4">
                    <?= $form->field($model, 'type_log')->checkBoxList([
                        //0=>'非四单四双',
                        0=>'除',
                        1=>'取',
                    ])->label('对数') ?>
                    </div>
                </div>

                <!--对数表单引入-->
                <?php include(dirname(__FILE__).'/log_form.php'); ?>

                <!--大小、单双模板引入-->
                <?php include(dirname(__FILE__).'/dx_ds.php');?>

                <!--分离数数表单引入-->
                <?php //include(dirname(__FILE__).'/fenli_shu_form.php'); ?>

                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <?= $form->field($model, 'hz')->checkboxList($hzArr)->label('投注类型(和值)') ?>
                    </div>
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:1-3-7-15-31-62-125-251') ?>
                    </div>
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'bet_while_miss')->textInput()->label('遗漏x期投,如:10') ?>
                    </div>
                </div>

                <!--动态过滤号码-->
                <?php include(dirname(__FILE__).'/filter_dynamic.php'); # 动态过滤号码 ?>

                <!--止盈止损-->
                <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>

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
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
$(function () {
    $('.checkbox-item').click(function() {
        var name = $(this).attr('name');
        $('input[name="' + name + '"]').not(this).prop('checked', false);
    });
    // Toggle dynamic filter 1
    $('#toggleFilterDynamic1').click(function() {
        $('#filterDynamic1Content').toggle();
        $(this).find('span').toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
    });

    // Toggle dynamic filter 2
    $('#toggleFilterDynamic2').click(function() {
        $('#filterDynamic2Content').toggle();
        $(this).find('span').toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
    });
});
</script>
