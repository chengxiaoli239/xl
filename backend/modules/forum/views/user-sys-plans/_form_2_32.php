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
                <?= Html::encode($this->title).'[三定-快译变换]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
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
                    <?= $form->field($model, 'single')->textInput() ?>

                <div class="row">
                    <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'code1')->textInput()->label('号码组一') ?>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'code2')->textInput()->label('号码组二') ?>
                    </div>
                </div>

                <?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:1-3-7-15-31-62-125-251') ?>
                    <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                    <!--?= $form->field($model, 'tz_type')->radioList($kArr)->label('投注类型') ?-->

                    <!--?= $form->field($model, 'buy_type')->textInput() ?-->


                    <!--?= $form->field($model, 'tz_sites')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'hz')->checkboxList($hzArr)->label('投注类型(和值)') ?-->

                    <!--?= $form->field($model, 'hz_Arr')->textInput()->label('上奖号码(四个数字一组)，多组英文逗号隔开') ?-->
                <div class="row">
                    <div class="col-lg-3 col-xs-4">
                        <?= $form->field($model, 'type_2')->checkBoxList([ 0=>'除', 1=>'取' ])->label('双重') ?>
                    </div>
                    <div class="col-lg-3 col-xs-4">
                        <?= $form->field($model, 'type_2b')->checkBoxList([ 0=>'除', 1=>'取' ])->label('两兄弟') ?>
                    </div>
                    <div class="col-lg-3 col-xs-4">
                        <?= $form->field($model, 'type_log')->checkBoxList([ 0=>'除', 1=>'取', ])->label('对数') ?>
                    </div>
                </div>
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
