<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\BtCrontabs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bt-crontabs-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'uid') ?>

    <?= $form->field($model, 'p_id') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'sName') ?>

    <?php // echo $form->field($model, 'sType') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'domain') ?>

    <?php // echo $form->field($model, 'echo') ?>

    <?php // echo $form->field($model, 'cycle') ?>

    <?php // echo $form->field($model, 'backupTo') ?>

    <?php // echo $form->field($model, 'save') ?>

    <?php // echo $form->field($model, 'where_minute') ?>

    <?php // echo $form->field($model, 'where_hour') ?>

    <?php // echo $form->field($model, 'where1') ?>

    <?php // echo $form->field($model, 'sBody') ?>

    <?php // echo $form->field($model, 'type_desc') ?>

    <?php // echo $form->field($model, 'urladdress') ?>

    <?php // echo $form->field($model, 'addtime') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
