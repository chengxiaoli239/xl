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
                <?= Html::encode($this->title).'[系统快捷]' ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'playway')->radioList([
                        //'1'=>'二字定',
                        //'2'=>'三字定',
                        '3'=>'四字定',
                    ])->label('投注方式') ?>
                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">

                    <!--?= $form->field($model, 'status')->textInput() ?-->
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'关闭',
                        '1'=>'开启',
                    ])->label('投注状态') ?>

                    <?= $form->field($model, 'single')->textInput() ?>

                    <?= $form->field($model, 'get_types')->checkboxList([1=>'三现:双重+两兄弟'])->label('投注类型(取)') ?>

                    <?= $form->field($model, 'cancel_types')->checkboxList([1=>'三现:双重+两兄弟'])->label('投注类型(除)') ?>

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
