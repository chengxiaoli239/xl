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
                    <?= $form->field($model, 'playway')->radioList([
                        //'1'=>'二字定',
                        //'2'=>'三字定',
                        '3'=>'四字定',
                    ])->label('投注方式') ?>

                    <!--?= $form->field($model, 'status')->textInput() ?-->
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'关闭',
                        '1'=>'开启',
                    ])->label('投注状态') ?>

                    <!--?= $form->field($model, 'single')->textInput() ?-->

                    <?= $form->field($model, 'import_codes_txt')->textInput()->label('号码跟倍数之间多组英文逗号或空格或分号隔开 2345:0.1,3456:0.2 或 2345 0.1 3456 0.2 或者 2345:0.1;3456:0.2') ?>
                    <input type="hidden" name="UserSysPlans[tz_type]" value="35"><!--四定导入 含倍数-->
                    <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                    <!--?= $form->field($model, 'tz_type')->radioList([ //'1'=>'大小单双三字定', //'2'=>'大小三字定', '3'=>'单双三字定', ])->label('投注类型') ?-->

                    <!--?= $form->field($model, 'buy_type')->textInput() ?-->
                    <!--?= $form->field($model, 'buy_type')->radioList([ '0'=>'反买', '1'=>'正买',])->label('购买方向') ?-->

                    <!--?= $form->field($model, 'nums')->textInput() ?-->

                    <!--?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:0.1-0.3-0.7-1.5-3.1-6.2-12.5-25.1') ?-->
                    <!--?= $form->field($model, 'plan_type')->radioList($plan_types)->label('计划类型，为"止盈止损"计划时须填以下两项') ?-->
                    <?= $form->field($model, 'take_profits')->textInput($plan_types)->label('止盈点(正数，例：3000)') ?>
                    <?= $form->field($model, 'stop_loss')->textInput($plan_types)->label('止损点(正数，例：4000)') ?>

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
