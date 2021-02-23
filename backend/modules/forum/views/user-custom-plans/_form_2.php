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

                    <!--?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?-->
                    <!--投注站点-->
                    <?= $form->field($model, 'tz_sites')->checkboxList($model->tz_sites) ?>

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
                    <?= $form->field($model, 'is_simulate')->radioList([
                        '0'=>'正常',
                        '1'=>'模拟',
                    ]) ?>
                    <?= $form->field($model, 'status')->radioList([
                        '0'=>'禁用',
                        '1'=>'激活',
                    ]) ?>

                    <?= $form->field($model, 'threshold_open')->textInput() ?>
                    <!--?= $form->field($model, 'periods_open')->textInput() ?-->
                    <?= $form->field($model, 'threshold_close')->textInput() ?>
                    <!--?= $form->field($model, 'periods_close')->textInput() ?-->
                    <!--?= $form->field($model, 'status')->textInput() ?-->

                    <?= $form->field($model, 'single')->textInput() ?>


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
