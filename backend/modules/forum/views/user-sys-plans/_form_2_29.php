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
                        <div class="col-lg-4 col-xs-4">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                '2'=>'三字定',
                                //'3'=>'四字定',
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
                        <?= $form->field($model, 'single')->textInput() ?>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <?= $form->field($model, 'arise')->textInput()->label('上奖')?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                    <!--位置合分：位置-->
                    <?= $form->field($model, 'hefen_pos')->checkboxList($hefen_pos)->label('1.1定位合分:取') ?>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <!--位置合分：合分-->
                    <?= $form->field($model, 'hefen')->textInput()->label('1.1定位合分:值')?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <!--位置合分：位置2-->
                    <?= $form->field($model, 'hefen_pos2')->checkboxList($hefen_pos)->label('1.2定位合分:取') ?>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <!--位置合分：合分2-->
                    <?= $form->field($model, 'hefen2')->textInput()->label('1.2定位合分:值')?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <!--位置合分：位置3-->
                    <?= $form->field($model, 'hefen_pos3')->checkboxList($hefen_pos)->label('1.3定位合分:取') ?>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <!--位置合分：合分3-->
                    <?= $form->field($model, 'hefen3')->textInput()->label('1.3定位合分:值')?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <!--位置合分：位置4-->
                    <?= $form->field($model, 'hefen_pos4')->checkboxList($hefen_pos)->label('1.4定位合分:取') ?>
                    <!--位置合分：合分4-->
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <?= $form->field($model, 'hefen4')->textInput()->label('1.4定位合分:值')?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <!--三定含除、取-->
                        <?= $form->field($model, 'arise_in_sel')->checkboxList([1=>'除',2=>'取'])->label('2.三字定含') ?>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <?= $form->field($model, 'arise_in')->textInput()->label('2.三字定含')?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <!--两数合、三数合-->
                    <?= $form->field($model, 'no_fix_hefen_pos')->checkboxList([1=>'两数',2=>'三数'])->label('3.不定位合分') ?>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                    <!--位置合分：合分-->
                    <?= $form->field($model, 'no_fix_hefen')->textInput()->label('3.不定位合分:值')?>
                    </div>
                </div>

                    <div class="row">
                        <div class="col-lg-3 col-xs-3"> <?= $form->field($model, 'p1')->textInput()->label('千') ?> </div>
                        <div class="col-lg-3 col-xs-3"> <?= $form->field($model, 'p2')->textInput()->label('百') ?> </div>
                        <div class="col-lg-3 col-xs-3"> <?= $form->field($model, 'p3')->textInput()->label('十') ?> </div>
                        <div class="col-lg-3 col-xs-3"> <?= $form->field($model, 'p4')->textInput()->label('个') ?> </div>
                    </div>

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
                            <?= $form->field($model, 'type_2b')->checkBoxList([
                            0=>'除',
                            1=>'取'
                        ])->label('两兄弟') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_3b')->checkBoxList([
                            0=>'除',
                            1=>'取'
                        ])->label('三兄弟') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'type_log')->checkBoxList([
                            //0=>'非四单四双',
                            0=>'除',
                            1=>'取',
                        ])->label('对数'); $form->field($model, 'stop_loss')->textInput($plan_types)->label('止损点(正数，例：4000)') ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:1-3-7-15-31-62-125-251') ?>
                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'bet_while_miss')->textInput()->label('遗漏x期投,如:10') ?>
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
