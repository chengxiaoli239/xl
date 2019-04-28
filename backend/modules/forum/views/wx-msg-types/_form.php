<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\WxMsgTypes */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="wx-msg-types-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'msg')->textarea(['rows' => 6]) ?>

                    <!--?= $form->field($model, 'status')->textInput() ?-->
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'关闭',
                        '1'=>'开启',
                    ])->label('状态') ?>

                    <!--?= $form->field($model, 'is_name')->textInput() ?-->
                    <?= $form->field($model, 'is_name')->radioList([
                        '0'=>'否',
                        '1'=>'是',
                    ])->label('内容加入名字?') ?>
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
