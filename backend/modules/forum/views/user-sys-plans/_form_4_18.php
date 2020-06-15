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
                <?= Html::encode($this->title).'[快选]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->
                    <div class="row">
                        <div class="col-lg-4">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                //'2'=>'三字定',
                                //'3'=>'四字定',
                                '4'=>'一字定',
                            ])->label('投注方式') ?>
                        </div>
                        <div class="col-lg-4">
                            <?= $form->field($model, 'is_test')->radioList([
                                '0'=>'真实',
                                '1'=>'模拟',
                            ])->label('真实/模拟') ?>
                        </div>
                        <div class="col-lg-4">
                            <?= $form->field($model, 'status')->radioList([
                                '0'=>'关闭',
                                '1'=>'开启',
                            ])->label('投注状态') ?>
                        </div>
                    </div>
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">

                    <?= $form->field($model, 'single')->textInput() ?>

                    <!--?= $form->field($model, 'arise')->textInput()->label('上奖') ?-->

                    <div class="row">
                        <div class="col-lg-3">
                            <?= $form->field($model, 'p1')->textInput()->label('第1位') ?>
                        </div>
                        <div class="col-lg-3">
                            <?= $form->field($model, 'p2')->textInput()->label('第2位') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            <?= $form->field($model, 'p3')->textInput()->label('第3位') ?>
                        </div>
                        <div class="col-lg-3">
                            <?= $form->field($model, 'p4')->textInput()->label('第4位') ?>
                        </div>
                        <div class="col-lg-3">
                            <?= $form->field($model, 'p5')->textInput()->label('第5位') ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:1-3-7-15-31-62-125-251') ?>

                    <?= $form->field($model, 'plan_type')->radioList($plan_types)->label('计划类型，为"止盈止损"计划时须填以下两项') ?>
                    <?= $form->field($model, 'take_profits')->textInput()->label('止盈点(正数，例：3000)') ?>
                    <?= $form->field($model, 'stop_loss')->textInput()->label('止损点(正数，例：4000)') ?>

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
