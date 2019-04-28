<?php

use yii\helpers\Html;
use izyue\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserFollowData */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-follow-data-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'playway')->textInput() ?-->

                    <!--?= $form->field($model, 'position')->textInput(['maxlength' => true]) ?-->
                    <?= $form->field($model, 'position')->radioList([
                            '1,2'=>'1,2位',
                            '1,3'=>'1,3位',
                            '1,4'=>'1,4位',
                            '2,3'=>'2,3位',
                            '2,4'=>'2,4位',
                            '3,4'=>'3,4位'
                    ]) ?>
                    <?= $form->field($model, 'codes_hezhi')->radioList([
                            '6'=>'6',
                            '7'=>'7',
                            '8'=>'8',
                            '9'=>'9',
                            '10'=>'10',
                            '11'=>'11',
                            '12'=>'12'
                    ]) ?>
                    <!--?= $form->field($model, 'is_follow')->radioList([ '0'=>'0', '1'=>'1', ]) ?-->
                    <?= $form->field($model, 'is_simulate')->radioList([
                        '0'=>'正常',
                        '1'=>'模拟',
                    ]) ?>
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'禁用',
                        '1'=>'激活',
                    ]) ?>

                    <!--?= $form->field($model, 'reference_codes')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'status')->textInput() ?-->

                    <?= $form->field($model, 'single')->textInput() ?>

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

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
