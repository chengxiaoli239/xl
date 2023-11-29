<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuthBackend */
/* @var $form yii\widgets\ActiveForm */
$this->title = '新建平台';
?>
<?php Pjax::begin(['id' => 'pjax-container']); ?>

<div class="eyun-auth-form row">
    <div class="col-lg-12 col-xs-12">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <?php $form = ActiveForm::begin(['options' => ['data-pjax' => true], 'id' => 'eyun-auth-form']); ?>
                <div class="row">
                    <div class="col-lg-3 col-xs-3">
                        <?= $form->field($model, 'type')->dropDownList(
                            \backend\models\EyunAuthBackend::PLATFORM_ID_OPTIONS,
                            ['prompt' => '-请选择-'] // Optional: Add a prompt message
                        ) ?>
                    </div>
                    <div class="col-lg-3 col-xs-3">
                        <?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-lg-3 col-xs-3">
                        <?= $form->field($model, 'password')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-lg-3 col-xs-3">
                        <?= $form->field($model, 'status')->textInput() ?>
                    </div>
                </div>

                <?= $form->field($model, 'authorization')->textarea(['rows' => 4, 'disabled'=>true]) ?>

                <?= $form->field($model, 'callback_url')->textInput(['maxlength' => true]) ?>

                <?= $form->field($model, 'base_url')->textInput(['maxlength' => true]) ?>

                <?= $form->field($model, 'desc')->textarea(['rows' => 4]) ?>

                <div class="form-group">
                    <div class="col-lg-offset-5 col-lg-5">
                        <?= Html::submitButton('Save', ['class' => 'btn btn-danger']) ?>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php Pjax::end(); ?>
