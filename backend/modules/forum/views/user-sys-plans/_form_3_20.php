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
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->
                    <input type="hidden" value="3" name="UserSysPlans[playway]">
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">

                    <div class="row">
                        <div class="col-lg-4 col-xs-4">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                //'2'=>'三字定',
                                '3'=>'四字定',
                            ])->label('类型') ?>
                        </div>
                        <div class="col-lg-4 col-xs-4">
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
                            <?= $form->field($model, 'single')->textInput()->label('倍(元)') ?>
                        </div>
                        <div class="col-lg-4 col-xs-6">
                            <?= $form->field($model, 'arise')->textInput()->label('上奖') ?>
                        </div>
                    </div>

                    <!--?= $form->field($model, 'tz_type')->textInput() ?-->
                    <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                    <?= $form->field($model, 'hz')->checkboxList($hzArr)
                        ->label('和值 
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_0_6">0-6</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_5_10">5-10</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_11_15">11-15</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_16_19">16-19</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_20_24">20-24</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_25_29">25-29</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_30_36">30-36</a>&nbsp;&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds">反买</a>
                            <a href="javascript:;" class="btn btn-xs btn-info reverse_type_4ds_Null">清</a>
                    ') ?>

                    <!--?= $form->field($model, 'buy_type')->textInput() ?-->

                    <!--?= $form->field($model, 'tz_sites')->textInput(['maxlength' => true]) ?-->
                    <div class="row">
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_2b')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('两兄弟') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_3b')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('三兄弟') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_4b')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('四兄弟') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_log')->checkBoxList([
                                //0=>'非四单四双',
                                0=>'除',
                                1=>'取',
                            ])->label('对数') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_2')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('双重') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_3')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('三重') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_4')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('四重') ?>
                        </div>
                        <div class="col-lg-3 col-xs-4">
                            <?= $form->field($model, 'type_22')->checkBoxList([
                                0=>'除',
                                1=>'取'
                            ])->label('双双重') ?>
                        </div>
                    </div>

                    <!--止盈止损-->
                    <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                    <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>
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
        $('.reverse_type_4ds').click(function () {
            $("[name='UserSysPlans[hz][]']").each(function () {
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            });
        });

        // 0-6
        $('.reverse_type_4ds_0_6').click(function () {
            arr = ['0','1','2','3','4','5','6']
            $("[name='UserSysPlans[hz][]']").each(function () {
                v = $.inArray($(this).val(), arr);
                console.log(v, $(this).val());
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
        $('.reverse_type_4ds_5_10').click(function () {
            arr = ['5','6','7','8','9','10']
            $("[name='UserSysPlans[hz][]']").each(function () {
                v = $.inArray($(this).val(), arr);
                console.log(v, $(this).val());
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
        $('.reverse_type_4ds_11_15').click(function () {
            arr = ['11','12','13','14','15']
            $("[name='UserSysPlans[hz][]']").each(function () {
                v = $.inArray($(this).val(), arr);
                console.log(v, $(this).val());
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
        $('.reverse_type_4ds_16_19').click(function () {
            arr = ['16','17','18','19']
            $("[name='UserSysPlans[hz][]']").each(function () {
                v = $.inArray($(this).val(), arr);
                console.log(v, $(this).val());
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
        $('.reverse_type_4ds_20_24').click(function () {
            arr = ['20','21','22','23','24']
            $("[name='UserSysPlans[hz][]']").each(function () {
                v = $.inArray($(this).val(), arr);
                console.log(v, $(this).val());
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
        $('.reverse_type_4ds_25_29').click(function () {
            arr = ['25','26','27','28','29']
            $("[name='UserSysPlans[hz][]']").each(function () {
                v = $.inArray($(this).val(), arr);
                console.log(v, $(this).val());
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
        $('.reverse_type_4ds_30_36').click(function () {
            arr = ['30','31','32','33','34','35','36']
            $("[name='UserSysPlans[hz][]']").each(function () {
                v = $.inArray($(this).val(), arr);
                console.log(v, $(this).val());
                if(v != -1){
                    if($(this).prop('checked') == false){
                        $(this).prop("checked",true);
                    }else {
                        $(this).prop("checked",false);
                    }
                }
            });
        });
        $('.reverse_type_4ds_Null').click(function () {
            $("[name='UserSysPlans[hz][]']").each(function () {
                $(this).prop("checked",false);
            });
        });
    });
</script>
