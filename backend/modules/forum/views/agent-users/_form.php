<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\AgentUsers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="agent-users-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'desc')->textInput(['maxlength' => true]) ?>

                    <!--?= $form->field($model, 'images')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'balance')->textInput(['maxlength' => true]) ?-->

                    <?= $form->field($model, 'is_tuo')->checkboxList(['1'=>'否', '2'=>'是'])?>

                    <?= $form->field($model, 'is_chi')->checkboxList(['1'=>'否', '2'=>'是'])?>

                    <?= $form->field($model, 'is_cha')->checkboxList(['1'=>'否', '2'=>'是'])?>

                    <?= $form->field($model, 'is_bind')->checkboxList(['1'=>'否', '2'=>'是'])?>
                    <!--?= $form->field($model, 'all_bet_money')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'bet_url')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'token')->textInput(['maxlength' => true]) ?-->

                    <!--?= $form->field($model, 'status')->textInput() ?-->

                    <!--?= //$form->field($model, 'created_at')->textInput() ?-->

                    <!--?= //$form->field($model, 'updated_at')->textInput() ?-->

                    <!--?= //$form->field($model, 'update_time')->textInput() ?-->

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
