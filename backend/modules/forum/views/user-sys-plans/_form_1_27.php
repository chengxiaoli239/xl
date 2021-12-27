<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-sys-plans-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                <!--?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?-->

                <!--?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?-->

                <input type="hidden" name="UserSysPlans[tz_type]" value="27"><!--三定导入-->
                <input type="hidden" name="UserSysPlans[lottery_type]" value="<?php echo $lottery_type;?>"><!--三定导入-->
                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'playway')->radioList([
                            '1'=>'二字定',
                            //'2'=>'三字定',
                            //'3'=>'四字定',
                        ])->label('投注方式') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <!--?= $form->field($model, 'status')->textInput() ?-->
                        <?= $form->field($model, 'status')->radioList([
                            '0'=>'关闭',
                            '1'=>'开启',
                        ])->label('投注状态') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'is_test')->radioList([
                            '0'=>'真',
                            '1'=>'模拟',
                        ])->label('真/模拟') ?>
                    </div>
                </div>
                <?= $form->field($model, 'single')->textInput() ?>

                <div class="row">
                    <div class="col-lg-12 col-xs-12">
                        <?= $form->field($model,"import_codes_txts[0]")->textarea([ 'autofocus' => false,'style'=>'height:100px' ])->label('多组英文逗号或空格隔开 23XX,34XX 或 23XX 34XX 或五位二定：XX3X4 XX3X3')?>
                    </div>
                </div>

                <!--排除前xx期-->
                <?php include(dirname(__FILE__).'/filter_xs_before.php'); ?>

                <!--导入号码组轮换-->
                <?php include(dirname(__FILE__).'/import_codes.php'); ?>

                <!--每期动态过滤-->

                <div class="row">
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:1-3-7-15-31-62-125-251') ?>
                    </div>
                    <div class="col-lg-3 col-xs-6">
                        <?= $form->field($model, 'bet_while_miss')->textInput()->label('遗漏x期投,如:10') ?>
                    </div>
                </div>

                <!--止盈止损-->
                <?php include(dirname(__FILE__).'/take_or_stop_profits.php'); ?>

                <?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>

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
