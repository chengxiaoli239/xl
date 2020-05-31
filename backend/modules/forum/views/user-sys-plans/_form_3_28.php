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

                    <input type="hidden" value="<?=$tz_type?>" name="UserSysPlans[tz_type]">
                    <div class="row">
                        <div class="col-lg-4">
                            <?= $form->field($model, 'playway')->radioList([
                                //'1'=>'二字定',
                                //'2'=>'三字定',
                                '3'=>'四字定',
                            ])->label('投注方式') ?>
                        </div>
                        <div class="col-lg-4">
                            <?= $form->field($model, 'is_test')->radioList([
                                '0'=>'真实',
                                '1'=>'模拟',
                            ])->label('真实/模拟') ?>
                        </div>
                        <div class="col-lg-4">
                            <!--?= $form->field($model, 'status')->textInput() ?-->
                            <?= $form->field($model, 'status')->radioList([
                                '0'=>'关闭',
                                '1'=>'开启',
                            ])->label('投注状态') ?>
                        </div>
                    </div>



                    <?= $form->field($model, 'single')->textInput() ?>

                    <!--位置合分：合分-->
                    <?= $form->field($model, 'xhefen')->textInput()->label('合分值')?>

                    <?= $form->field($model, 'get_types')->checkboxList($code_types)->label('类型【取】') ?>
                    <?= $form->field($model, 'remove_types')->checkboxList($code_types)->label('类型【除】') ?>

                    <?= $form->field($model, 'get_hzs')->checkboxList($hzArr)->label('和值【取】') ?>
                    <?= $form->field($model, 'remove_hzs')->checkboxList($hzArr)->label('和值【除】') ?>

                    <div class="row">
                        <div class="col-lg-3">
                            <?= $form->field($model, 'get_arises')->textInput()->label('上奖【取】') ?>
                        </div>
                        <div class="col-lg-3">
                            <?= $form->field($model, 'remove_arises')->textInput()->label('上奖【除】') ?>
                        </div>
                    </div>

                <?= include (dirname(__FILE__).'/../common/common_input.php');?>

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
