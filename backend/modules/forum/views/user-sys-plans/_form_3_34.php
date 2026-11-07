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
                <?php include __DIR__.'/real_bet_window.php'; ?>
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
                        <?= $form->field($model,"import_codes_txts[0]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-xs-12">
                        <?= $form->field($model, 'use_platform_txt_import')->radioList([0=>'原导入方式', 1=>'盘口 TXT 导入'])->label('本地客户端下注方式') ?>
                    </div>
                    <div class="col-lg-8 col-xs-12">
                        <label for="platform-txt-file">TXT 文件</label>
                        <input id="platform-txt-file" type="file" accept=".txt,text/plain" class="form-control">
                        <small id="platform-txt-feedback" class="help-block"></small>
                    </div>
                </div>
                <input type="hidden" name="UserSysPlans[tz_type]" value="34"><!--四定导入-->
                <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                <!--?= $form->field($model, 'tz_type')->radioList([ //'1'=>'大小单双三字定', //'2'=>'大小三字定', '3'=>'单双三字定', ])->label('投注类型') ?-->

                <!--?= $form->field($model, 'buy_type')->textInput() ?-->
                <!--?= $form->field($model, 'buy_type')->radioList([ '0'=>'反买', '1'=>'正买',])->label('购买方向') ?-->

                <!--?= $form->field($model, 'nums')->textInput() ?-->

                <!--排除前xx期-->
                <?php //include(dirname(__FILE__).'/filter_xs_before.php'); # 功能完好，不常用先注释 ?>
                <?php include(dirname(__FILE__).'/history_simulate_bet.php'); # 模拟历史 ?>

                <!--动态过滤号码-->
                <?php include(dirname(__FILE__).'/filter_dynamic.php'); # 动态过滤号码 ?>

                <?php include(dirname(__FILE__).'/A_x_arise_B_y_arise_bet_B.php'); # A出x次B出y次投B ?>

                <!--导入号码组轮换-->
                <?php include(dirname(__FILE__).'/import_codes.php'); ?>

                <!--区间遗漏盈利止盈止损-->
                <?php if(isset(SscDataService::PLAN_TYPE_OPTIONS[SscDataService::PLAN_TYPE_AREA_SINGLES_BET])){ include(dirname(__FILE__).'/take_profits_area.php');} ?>


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
<script>
(function () {
    var fileInput = document.getElementById('platform-txt-file');
    var codesInput = document.querySelector('[name="UserSysPlans[import_codes_txts][0]"]');
    var moneyInput = document.getElementById('usersysplans-single');
    var feedback = document.getElementById('platform-txt-feedback');
    if (!fileInput || !codesInput || !moneyInput) return;
    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0];
        if (!file) return;
        if (!/\.txt$/i.test(file.name) || file.size > 128 * 1024) {
            feedback.textContent = '请选择不超过 128 KB 的 TXT 文件';
            fileInput.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function () {
            var entries = String(reader.result || '').trim().split(/[\s,，]+/).filter(Boolean);
            var single = Number(moneyInput.value);
            var codes = [];
            if (!entries.length || entries.length > 2500 || !Number.isFinite(single) || single < 0.1) {
                feedback.textContent = '请检查号码数量和计划单注金额';
                return;
            }
            for (var i = 0; i < entries.length; i++) {
                var match = /^([0-9]{4})(?:=([0-9]+(?:\.[0-9]+)?))?$/.exec(entries[i]);
                if (!match || (match[2] && Math.abs(Number(match[2]) - single) > 0.000001)) {
                    feedback.textContent = '仅支持四位号码；文件中的每注金额须与计划单注金额一致';
                    return;
                }
                codes.push(match[1]);
            }
            codesInput.value = codes.join(' ');
            feedback.textContent = '已载入 ' + codes.length + ' 注';
            var option = document.querySelector('[name="UserSysPlans[use_platform_txt_import]"][value="1"]');
            if (option) option.checked = true;
        };
        reader.onerror = function () { feedback.textContent = '读取 TXT 文件失败'; };
        reader.readAsText(file);
    });
})();
</script>
