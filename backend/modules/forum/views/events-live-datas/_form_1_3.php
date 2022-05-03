<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
$SscKjDatas = \backend\models\SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->asArray()->limit(18)->all();
$this->title = '比赛绑定';
?>
<style>
    p {
        word-break:break-all;
    }
</style>
<div class="user-sys-plans-form row">
    <div class="col-lg-10">
        <section class="panel">
            <header class="panel-heading">
                <?php include(dirname(__FILE__).'/create_tab.php');?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        //'inputOptions'=>['class'=>'p-1'],
                    ],
                ]); ?>

                <?php //include(dirname(__FILE__).'/act-button.php');?>

                <input type="hidden" id="sport_type" name="SportType[sport_type]" value="<?=$sport_type?>">
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
    <div class="col-lg-2">
        <section class="panel">
            <header class="panel-heading">
                <?= '<strong><font color="green">最新记录</font>&nbsp;&nbsp;&nbsp;</strong>' ?>
            </header>
            <?php foreach ($SscKjDatas as $sscKjData){ ?>
                <header class="panel-heading" title="<?=substr($sscKjData['update_time'],10)?>"><strong><?=$sscKjData['qihao'] ?> &nbsp; <font color="green"><?=$sscKjData['code_str'] ?></font></strong>&nbsp;&nbsp; &nbsp; <?=$sscKjData['codes_4nums_hz']?></header>
            <?php } ?>
        </section>
    </div>
</div>

<div class="modal fade" id="rstTipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit; width: 1000px; height: 800px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">提示信息</h4>
            </div>
            <div class="modal-body" style="display:block;padding: 8px; width:100%;height: 320px;overflow-y: scroll">
                <div class="form-group up-reason">
                    <label id="tip_msg_rst" for="tip_msg_rst"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="opRstConfirm">确定</button>
            </div>
        </div>
    </div>
</div>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
$(function () {

});
</script>