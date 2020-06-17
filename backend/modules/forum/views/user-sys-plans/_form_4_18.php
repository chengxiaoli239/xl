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
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                //'2'=>'三字定',
                                //'3'=>'四字定',
                                '4'=>'一字定',
                            ])->label('类型') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'is_test')->radioList([
                                '0'=>'真实',
                                '1'=>'模拟',
                            ])->label('真/模拟') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'status')->radioList([
                                '0'=>'关闭',
                                '1'=>'开启',
                            ])->label('状态') ?>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <?= $form->field($model, 'single')->textInput()->label('倍(元)') ?>
                        </div>
                    </div>
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">
                    <!--?= $form->field($model, 'arise')->textInput()->label('上奖') ?-->

                    <div class="row">
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'p1')->textInput()->label('千') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'p2')->textInput()->label('百') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'p3')->textInput()->label('十') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'p4')->textInput()->label('个') ?>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <?= $form->field($model, 'p5')->textInput()->label('五') ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:1-3-7-15-31-62-125-251') ?>

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
