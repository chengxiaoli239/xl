<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $form yii\widgets\ActiveForm */
use common\models\AdminModel;
$username = AdminModel::findOne($uid)->username;
?>

<div class="user-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title).' ： '.$username; ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'tz_systems_ids')->checkboxList($allSystems)->label('投注网点') ?>

                    <?= $form->field($model, 'tz_types')->checkboxList($allTzTypes)->label('投注方式tz_types') ?>

                    <?= $form->field($model, 'lottery_types')->checkboxList($allLotteryTypes)->label('彩种lottery_types') ?>

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
