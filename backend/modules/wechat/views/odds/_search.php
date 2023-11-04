<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\Odds */
/* @var $form yii\widgets\ActiveForm */
$PlayMethods = \common\models\thirdD\PlayMethod::find()->where(['status'=>1])->asArray()->all();
$datas = array_column($PlayMethods, 'name', 'id');
$playMethodOptions = array_merge(['' => '--请选择--'], $datas);
?>

<div class="odds-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'play_method_id')->dropDownList(
                $playMethodOptions, // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('玩法')?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'name') ?>
        </div>
        <!--
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'money') ?>
        </div>
        -->
        <div class="col-lg-2 col-xs-3">
            <label> </label>
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>
