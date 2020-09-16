<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Matchs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="matchs-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                    <?= $form->field($model, 'system_id')->textInput() ?>

                    <?= $form->field($model, 'game_type')->textInput() ?>

                    <?= $form->field($model, 'game_type_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'g_id')->textInput() ?>

                    <?= $form->field($model, 'game_id')->textInput() ?>

                    <?= $form->field($model, 'game_name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'player_1')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'player_2')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'player_1_water')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'player_2_water')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'status')->textInput() ?>

                    <?= $form->field($model, 'is_bind')->textInput() ?>

                    <?= $form->field($model, 'bind_id')->textInput() ?>

                    <?= $form->field($model, 'type')->textInput() ?>

                    <?= $form->field($model, 'created_at')->textInput() ?>

                    <?= $form->field($model, 'updated_at')->textInput() ?>

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
