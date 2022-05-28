<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
$SscKjDatas = \backend\models\SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->asArray()->limit(30)->all();
$this->title = '比赛绑定';
# 网盘比赛
$SportsPlatesGames = \backend\models\sports\SportsPlatesGames::find()->where('1=1')->orderBy(['id'=>SORT_DESC])->asArray()->limit(30)->all();
# 比分网
$EventsLiveDatas = \backend\models\sports\EventsLiveDatas::find()->where('1=1')->orderBy(['id'=>SORT_DESC])->asArray()->limit(30)->all();
?>
<style>
    p {
        word-break:break-all;
    }
</style>
<div class="user-sys-plans-form row">
    <div class="col-lg-1">
        <header class="panel-heading">
            <?php include(dirname(__FILE__).'/create_tab.php');?>
        </header>
        <input type="hidden" id="sport_type" name="SportType[sport_type]" value="<?=$sport_type?>">
    </div>
    <div class="col-lg-5">
        <section class="panel">
            <header class="panel-heading">
                <?= '<strong><font color="green">网盘比赛</font>&nbsp;&nbsp;&nbsp;</strong>' ?>
            </header>
            <?php foreach ($SportsPlatesGames as $SportsPlatesGame){ ?>
                <header class="panel-heading" title="<?=substr($SportsPlatesGame['update_time'],10)?>">
                    [<?=$SportsPlatesGame['game_schedule']? : ' '?>]&nbsp;&nbsp;
                    <strong><?=$SportsPlatesGame['league_matches_name'] ?> <font color="green"><?=str_replace(' ', '', $SportsPlatesGame['score']) ?></font></strong>
                    ：<font color="#a52a2a"><?=$SportsPlatesGame['name1'] ?> - <?=$SportsPlatesGame['name2'] ?></font>
                </header>
            <?php } ?>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel">
            <header class="panel-heading">
                <?= '<strong><font color="green">比分网</font>&nbsp;&nbsp;&nbsp;</strong>' ?>
            </header>
            <?php foreach ($EventsLiveDatas as $EventsLiveData){ ?>
                <header class="panel-heading" title="<?=substr($EventsLiveData['update_time'],10)?>">
                    [<?=$EventsLiveData['clock_minute'].':'.$EventsLiveData['clock_second']?>] <strong><?=$EventsLiveData['event_name_en'] ?> &nbsp;<br> <font color="green"><?=$EventsLiveData['score_home'] ?>-<?=$EventsLiveData['score_away'] ?></font></strong>
                    ：<font color="#a52a2a"><?=$EventsLiveData['home_name_en'] ?> - <?=$EventsLiveData['way_name_en'] ?></font>
                </header>
            <?php } ?>
        </section>
    </div>
</div>
<div class="row">
    <div class="col-lg-1">
        <header class="panel-heading">
        </header>
    </div>
    <div class="col-lg-10">
        <section class="panel">
            <header class="panel-heading">
                <?= '<strong><font color="green">已绑定比赛</font>&nbsp;&nbsp;&nbsp;</strong>' ?>
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