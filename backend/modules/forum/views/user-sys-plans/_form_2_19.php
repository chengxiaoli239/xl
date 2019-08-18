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
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'playway')->radioList([
                        //'1'=>'二字定',
                        '2'=>'三字定',
                        //'3'=>'四字定',
                    ])->label('投注方式') ?>

                    <!--?= $form->field($model, 'status')->textInput() ?-->
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'关闭',
                        '1'=>'开启',
                    ])->label('投注状态') ?>

                    <?= $form->field($model, 'single')->textInput() ?>

                    <?= $form->field($model, 'import_codes_txt')->textInput()->label('上奖号码(三个数字一组)，多组英文逗号或者空格隔开') ?>
                    <input type="hidden" name="UserSysPlans[tz_type]" value="19"><!--三定导入-->
                    <!-- 1大小单双三字定2大小三字定3单双三字定 -->
                    <!--?= $form->field($model, 'tz_type')->radioList([ //'1'=>'大小单双三字定', //'2'=>'大小三字定', '3'=>'单双三字定', ])->label('投注类型') ?-->

                    <!--?= $form->field($model, 'buy_type')->textInput() ?-->
                    <!--?= $form->field($model, 'buy_type')->radioList([ '0'=>'反买', '1'=>'正买',])->label('购买方向') ?-->

                    <!--?= $form->field($model, 'nums')->textInput() ?-->

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
