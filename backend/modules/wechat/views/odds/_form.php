<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\Odds */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('app', 'Update Odds');
?>

<div class="odds-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <!--?= $form->field($model, 'user_id')->textInput() ?-->

                    <!--?= $form->field($model, 'play_method_id')->textInput() ?-->

                    <!--?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?-->

                <div class="row">
                    <div class="col-lg-2 col-xs-3">
                        <?= $form->field($model, 'money')->textInput(['maxlength' => true]) ?>
                    </div>

                    <div class="col-lg-2 col-xs-3">
                        <?= $form->field($model, 'bouns')->textInput(['maxlength' => true]) ?>
                    </div>

                    <!--?= $form->field($model, 'odds')->textInput(['maxlength' => true]) ?-->

                    <div class="col-lg-2 col-xs-3">
                        <?= $form->field($model, 'status')->radioList([
                            '0'=>'关闭',
                            '1'=>'开启',
                        ])->label('状态') ?>
                    </div>

                    <!--?= $form->field($model, 'created_at')->textInput() ?-->

                    <!--?= $form->field($model, 'updated_at')->textInput() ?-->

                    <!--?= $form->field($model, 'update_at')->textInput() ?-->

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <?= Html::submitButton('保存', ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
