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
                    <?= $form->field($model, 'position_1')->checkboxList([
                        '1'=>'大',
                        '2'=>'小',
                        '3'=>'单',
                        '4'=>'双',
                    ]) ?>
                    <?= $form->field($model, 'position_2')->checkboxList([
                        '1'=>'大',
                        '2'=>'小',
                        '3'=>'单',
                        '4'=>'双',
                    ]) ?>
                    <?= $form->field($model, 'position_3')->checkboxList([
                        '1'=>'大',
                        '2'=>'小',
                        '3'=>'单',
                        '4'=>'双',
                    ]) ?>
                    <?= $form->field($model, 'position_4')->checkboxList([
                            '1'=>'大',
                            '2'=>'小',
                            '3'=>'单',
                            '4'=>'双',
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
