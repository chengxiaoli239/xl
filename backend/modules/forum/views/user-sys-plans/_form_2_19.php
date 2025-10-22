<?php

use backend\service\SscDataService;
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
                <div class="row">
                    <div class="col-lg-3 col-xs-4">
                        <?= $form->field($model, 'playway')->radioList([
                            //'1'=>'二字定',
                            '2'=>'三字定',
                            //'3'=>'四字定',
                        ])->label('类型') ?>
                    </div>
                    <div class="col-lg-3 col-xs-4">
                        <!--?= $form->field($model, 'status')->textInput() ?-->
                        <?= $form->field($model, 'status')->radioList([
                            '0'=>'关闭',
                            '1'=>'开启',
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
                        <?= $form->field($model, 'is_init_perdate')->checkboxList(
                            [1=>'否',2=>'是'],
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
                        )->label('每天初始化(翻倍计划)') ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 col-xs-12">
                        <?= $form->field($model,"import_codes_txts[0]")->textarea([ 'autofocus' => false,'style'=>'height:100px' ])->label('多组英文逗号或空格隔开 234X,345X 或 234X 345X')?>
                    </div>
                </div>
                <input type="hidden" name="UserSysPlans[tz_type]" value="19"><!--三定导入-->
                <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                <!--?= $form->field($model, 'tz_type')->radioList([ //'1'=>'大小单双三字定', //'2'=>'大小三字定', '3'=>'单双三字定', ])->label('投注类型') ?-->

                <!--?= $form->field($model, 'buy_type')->textInput() ?-->
                <!--?= $form->field($model, 'buy_type')->radioList([ '0'=>'反买', '1'=>'正买',])->label('购买方向') ?-->

                <!--?= $form->field($model, 'nums')->textInput() ?-->

                <!--排除前xx期-->
                <?php //include(dirname(__FILE__).'/filter_xs_before.php'); # 功能完好，不常用先注释 ?>
                <?php include(dirname(__FILE__).'/history_simulate_bet.php'); # 模拟历史 ?>
                <?php include(dirname(__FILE__).'/A_x_arise_B_y_arise_bet_B.php'); # A出x次B出y次投B ?>
                <!--导入号码组轮换-->
                <?php include(dirname(__FILE__).'/import_codes.php'); ?>


                <?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:0.1-0.3-0.7-1.5-3.1-6.2-12.5-25.1') ?>
                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'bet_while_miss')->textInput()->label('遗漏x期投,如:10') ?>
                        </div>
                    </div>

                    <!--区间盈利止盈止损-->
                    <?php if(isset(SscDataService::PLAN_TYPE_OPTIONS[SscDataService::PLAN_TYPE_AREA_SINGLES_BET])){ include(dirname(__FILE__).'/take_profits_area.php');} ?>


                    <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                    <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>

                    <div class="form-group">
                        <div class="col-lg-offset-6 col-lg-10 ml-auto">
                            <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
