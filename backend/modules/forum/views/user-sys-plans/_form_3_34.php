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
                <div class="row">
                    <div class="col-lg-3 col-xs-4">
                        <?= $form->field($model, 'playway')->radioList([
                            //'1'=>'二字定',
                            //'2'=>'三字定',
                            '3'=>'四字定',
                        ])->label('类型') ?>
                    </div>
                    <div class="col-lg-3 col-xs-4">
                        <?= $form->field($model, 'status')->radioList([
                            '0'=>'关',
                            '1'=>'开',
                        ])->label('状态') ?>
                    </div>
                    <div class="col-lg-3 col-xs-4">
                        <?= $form->field($model, 'is_test')->radioList([
                            '0'=>'真',
                            '1'=>'模拟',
                        ])->label('真/模拟') ?>
                    </div>
                </div>
                <?= $form->field($model, 'single')->textInput() ?>

                <div class="row">
                    <div class="col-lg-12 col-xs-12">
                        <?= $form->field($model,"import_codes_txts[0]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
                    </div>
                </div>
                <input type="hidden" name="UserSysPlans[tz_type]" value="34"><!--四定导入-->
                <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                <!--?= $form->field($model, 'tz_type')->radioList([ //'1'=>'大小单双三字定', //'2'=>'大小三字定', '3'=>'单双三字定', ])->label('投注类型') ?-->

                <!--?= $form->field($model, 'buy_type')->textInput() ?-->
                <!--?= $form->field($model, 'buy_type')->radioList([ '0'=>'反买', '1'=>'正买',])->label('购买方向') ?-->

                <!--?= $form->field($model, 'nums')->textInput() ?-->

                <!--导入号码组轮换-->
                <?php include(dirname(__FILE__).'/import_codes.php'); ?>

                <?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:1-3-7-15-31-62-125-251') ?>

                <div class="row">
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'bet_while_miss')->textInput()->label('遗漏x期投,如:10') ?>
                    </div>
                </div>

                <!--止盈止损-->
                <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                <!--?= $form->field($model, 'tz_sites')->textInput(['maxlength' => true]) ?-->
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
$('#usersysplans-change_per').click(function () {
    console.log('xxxx');
    $("[name='UserSysPlans[change_per][]']").each(function () {
        flag = $(this).prop('checked');
        console.log(flag);
        if(flag == true){
            console.log('aaaa');
            $(".import_codes_txts").each(function () {
                $(this).removeClass('hide')
                $(this).show()
            });
        }else {
            $(".import_codes_txts").each(function () {
                $(this).hide();
            });
        }
    });
});
</script>
