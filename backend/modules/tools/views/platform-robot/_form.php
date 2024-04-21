<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\open\PlatformRobot */
/* @var $form yii\widgets\ActiveForm */
$this->title = '新建/编辑机器人';

?>
<style>
    .wrapper {
        margin-top: 12px;
    }
</style>
<div class="platform-robot-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                <div class="row">
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'platform_id')->dropDownList(
                                \common\helpers\Platform::TYPE_OPTIONS,
                            ['prompt' => '-请选择-'] // Optional: Add a prompt message
                        ) ?>
                    </div>
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'name')->label('机器人TG名称')->textInput(['maxlength' => true]) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-10 col-xs-12">
                        <?= $form->field($model, 'token')->textarea(['rows' => 4]) ?>
                    </div>

                    <div class="col-lg-10 col-xs-12">
                        <?= $form->field($model, 'remark')->textarea(['rows' => 4]) ?>
                    </div>
                </div>

                    <div class="form-group">
                        <div class="col-lg-offset-8 col-lg-10 col-xs-offset-8">
                            <?= Html::submitButton('保存', ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
