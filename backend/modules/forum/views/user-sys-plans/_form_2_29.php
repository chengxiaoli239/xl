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
                <?= Html::encode($this->title).'[快选-三字定]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'playway')->radioList([
                        //'1'=>'二字定',
                        '2'=>'三字定',
                        //'3'=>'四字定',
                    ])->label('投注方式') ?>
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">

                    <?= $form->field($model, 'is_test')->radioList([
                        '0'=>'真实',
                        '1'=>'模拟',
                    ])->label('真实/模拟') ?>
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">

                    <!--?= $form->field($model, 'status')->textInput() ?-->
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'关闭',
                        '1'=>'开启',
                    ])->label('投注状态') ?>

                    <?= $form->field($model, 'single')->textInput() ?>

                    <!--位置合分：位置-->
                    <?= $form->field($model, 'hefen_pos')->checkboxList($hefen_pos)->label('1.定位合分取:位置') ?>
                    <!--位置合分：合分-->
                    <?= $form->field($model, 'hefen')->textInput()->label('1.定位合分:值')?>

                    <!--两数合、三数合-->
                    <?= $form->field($model, 'no_fix_hefen_pos')->checkboxList([1=>'两数和',2=>'三数合'])->label('2.不定位合分') ?>
                    <!--位置合分：合分-->
                    <?= $form->field($model, 'no_fix_hefen')->textInput()->label('2.不定位合分:值')?>

                    <!--三定含除、取-->
                    <?= $form->field($model, 'arise_in_sel')->checkboxList([1=>'除',2=>'取'])->label('3.三字定含') ?>
                    <?= $form->field($model, 'arise_in')->textInput()->label('3.三字定含')?>

                    <?= $form->field($model, 'arise')->textInput()->label('上奖')?>

                    <?= $form->field($model, 'p1')->textInput()->label('千位') ?>

                    <?= $form->field($model, 'p2')->textInput()->label('百位') ?>

                    <?= $form->field($model, 'p3')->textInput()->label('十位') ?>

                    <?= $form->field($model, 'p4')->textInput()->label('个位') ?>

                    <?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:1-3-7-15-31-62-125-251') ?>
                    <!--?= $form->field($model, 'tz_type')->textInput() ?-->
                    <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                    <!--?= $form->field($model, 'tz_type')->radioList($kArr)->label('投注类型') ?-->

                    <!--?= $form->field($model, 'buy_type')->textInput() ?-->

                    <!--?= $form->field($model, 'tz_sites')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'hz')->checkboxList($hzArr)->label('三数和值') ?-->

                    <!--?= $form->field($model, 'hz_Arr')->textInput()->label('上奖号码(四个数字一组)，多组英文逗号隔开') ?-->
                    <?= $form->field($model, 'type_2')->checkBoxList([
                        0=>'除',
                        1=>'取'
                    ])->label('双重') ?>
                    <?= $form->field($model, 'type_3')->checkBoxList([
                        0=>'除',
                        1=>'取'
                    ])->label('三重') ?>
                    <?= $form->field($model, 'type_2b')->checkBoxList([
                        0=>'除',
                        1=>'取'
                    ])->label('两兄弟') ?>
                    <?= $form->field($model, 'type_3b')->checkBoxList([
                        0=>'除',
                        1=>'取'
                    ])->label('三兄弟') ?>
                    <?= $form->field($model, 'type_log')->checkBoxList([
                        //0=>'非四单四双',
                        0=>'除',
                        1=>'取',
                    ])->label('对数'); $form->field($model, 'stop_loss')->textInput($plan_types)->label('止损点(正数，例：4000)') ?>

                    <?= $form->field($model, 'plan_type')->radioList($plan_types)->label('计划类型，为"止盈止损"计划时须填以下两项') ?>
                    <?= $form->field($model, 'take_profits')->textInput($plan_types)->label('止盈点(正数，例：3000)') ?>
                    <?= $form->field($model, 'stop_loss')->textInput($plan_types)->label('止损点(正数，例：4000)') ?>

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
