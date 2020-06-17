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
                <?= Html::encode($this->title).'[二定-号码变换]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'playway')->radioList([
                        '1'=>'二字定',
                        //'2'=>'三字定',
                        //'3'=>'四字定',
                    ])->label('投注方式') ?>
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">
                    <input type="hidden" value="<?=$model->status_val?>" name="UserSysPlans[status_val]">

                <!--?= $form->field($model, 'status')->textInput() ?-->
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'关闭',
                        '1'=>'开启',
                    ])->label('投注状态') ?>

                <?= $form->field($model, 'single')->textInput() ?>

                <?= $form->field($model, 'code1')->textInput()->label('号码组一') ?>
                    <?= $form->field($model, 'code2')->textInput()->label('号码组二') ?>

                    <?= $form->field($model, 'singles')->textInput()->label('倍数梯度,如:1-2-4-8-16-32-42-84') ?>

                    <!--?= $form->field($model, 'hz')->checkboxList($hzArr)->label('投注类型(和值)') ?-->

                    <!--?= $form->field($model, 'hz_Arr')->textInput()->label('上奖号码(四个数字一组)，多组英文逗号隔开') ?-->
                    <?= $form->field($model, 'type_2')->checkBoxList([ 0=>'除', 1=>'取' ])->label('双重') ?>
                    <?= $form->field($model, 'type_2b')->checkBoxList([ 0=>'除', 1=>'取' ])->label('两兄弟') ?>
                    <?= $form->field($model, 'type_log')->checkBoxList([ 0=>'除', 1=>'取' ])->label('对数') ?>

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
