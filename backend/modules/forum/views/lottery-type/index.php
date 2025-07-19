<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\LotteryType */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Lottery Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="lottery-type-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Lottery Type'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'sort',
                        //'lottery_type',
                        //'title',
                        ['attribute' => 'title','label'=>'名称',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->title;
                            }
                        ],
                        ['attribute' => 'lottery_type','label'=>'系统lottery_type',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                //return  \backend\service\Config_Base::lotteryTypeLists($model->lottery_type);
                                return  $model->lottery_type;
                            }
                        ],
                        //'shortName',
                        ['attribute' => 'shortName','label'=>'简称',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->shortName;
                            }
                        ],
                        //'enable',
                        ['attribute' => 'enable','label'=>'开启状态','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url0 = "/forum/lottery-type/switch-status?id=".$model->id.'&val=1&field=enable'; # 点击开启
                                $url1 = "/forum/lottery-type/switch-status?id=".$model->id.'&val=0&field=enable'; # 点击关闭
                                if($model->enable == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->enable){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                //return \backend\service\Config_Base::dropDown('enable', $model->enable);
                            },
                            'filter' => \backend\service\Config_Base::dropDown('enable'),
                        ],
                        ['attribute' => 'grabDataStatus','label'=>'号码抓取','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url0 = "/forum/lottery-type/switch-status?id=".$model->id.'&val=1&field=grabDataStatus'; # 点击开启
                                $url1 = "/forum/lottery-type/switch-status?id=".$model->id.'&val=0&field=grabDataStatus'; # 点击关闭
                                if($model->grabDataStatus == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->grabDataStatus){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                //return \backend\service\Config_Base::dropDown('enable', $model->enable);
                            },
                            'filter' => \backend\service\Config_Base::dropDown('grabDataStatus'),
                        ],
                        //'isDelete',
                        //'name',
                        //'codeList',
                        ['attribute' => 'codeList','label'=>'号码',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->codeList;
                            }
                        ],
                        ['attribute' => 'enable','label'=>'本期状态','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = "<font color='green'>开启</font><span id='tip_msg_'".$model->lottery_type."></span>" ;
                                return Html::a($txt, '#', ['title'=>'点击开启', 'class'=>'btn btn-xs btn-primary open-bet-status', 'lottery_type'=>$model->lottery_type]);
                            },
                        ],
                        //'info',
                        ['attribute' => 'info','label'=>'描述',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->info;
                            }
                        ],
                        ['attribute'=>'status', 'label'=>'操作','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $init_flag = \Yii::$app->cache->get(\backend\service\SystemService::getInitLotteryDataKey($model->lottery_type));
                                $init_txt = '';
                                if($init_flag){
                                    $init_txt = '...';
                                }
                                $url1 = '/forum/lottery-type/init-lottery'; # 初始化系统数据
                                $url2 = '/forum/lottery-type/del-bet-record'; # 清除游戏记录
                                $txt = "<button data-url='".$url1."' color='green' data-lottery-type='".$model->lottery_type."' class='btn btn-info btn-xs act-execute'>初始化".$init_txt."</button>" ;
                                $txt .= "<button data-url='".$url2."' color='red' data-lottery-type='".$model->lottery_type."' class='btn btn-warning btn-xs act-execute'>清理下注数据</button>" ;
                                return Html::a($txt, 'javascript:;', ['title' => '点击执行，新加彩种初始化']);
                            }
                        ],
                        //'onGetNoed',
                        ['attribute' => 'onGetNoed','label'=>'事件函数',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->onGetNoed;
                            }
                        ],
                        ['attribute' => 'data_ftime','label'=>'时间间隔(s)',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->data_ftime;
                            }
                        ],
                        //'defaultViewGroup',
                        //'android',
                        //'num',
                        ['attribute' => 'typeGroupName','label'=>'彩种类别',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->typeGroupName;
                            }
                        ],

                        ['attribute' => 'enable','label'=>'操作','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = "<font color='green'>单双数据</font><span id='tip_msg_init_ds_'".$model->lottery_type."></span>" ;
                                return Html::a($txt, '#', ['title'=>'点击初始化', 'class'=>'btn btn-xs btn-primary init-ds-data', 'lottery_type'=>$model->lottery_type]);
                            },
                        ],

                        ['class' => 'yii\grid\ActionColumn','headerOptions'=>['width'=>'5%'],'template'=>'{update}&nbsp;&nbsp;&nbsp;&nbsp;{delete}'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<div class="modal fade" id="tipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title"></h4>
            </div>
            <div class="modal-body">
                <div class="form-group up-reason">
                    <span id="tip_msg"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="opConfirm">确定</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" style="max-height: 500px;margin-top: 100px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">提示信息</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>推送结果：</strong>
                    <pre><code id="rst_code"></code></pre>
                    <strong>推送内容：</strong>
                    <pre><code id="push_content"></code></pre>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">确定</button>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="act-val" name="act-val" value="">
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
$(function () {
    $('.act-execute').click(function () {
        domain = window.location.protocol + "//" + window.location.host;
        url = $(this).data('url');
        lottery_type = $(this).data('lottery-type');
        console.log(domain)
        $.post(url, {lottery_type: lottery_type}, function (rst) {
            $('#rst_code').text(JSON.stringify(rst.rstData, null, ' '))
            $('#push_content').text(JSON.stringify(rst.post, null, ' '))
            $('#exampleModal_msg').modal('show');
        });
    });

    function openBetStatus(lottery_type) {
        var data = {lottery_type:lottery_type};
        var tip_title = '';
        $.post("/forum/lottery-type/open-bet-status",data,function(rst) {
            console.log(rst);
            if(rst.status === 200) {
                tip_title = '操作成功';
                msg = rst.msg;
                $("#balance_"+id).html(msg);
            } else {
                tip_title = '操作失败';
            }
            //showTips(null, rst.msg, tip_title); # 同步完无需弹框，暂且注释
        },'JSON');
    }

    function initDsData(lottery_type) {
        var data = {lottery_type:lottery_type};
        var tip_title = '';
        $.post("/forum/lottery-type/init-ds-datas",data,function(rst) {
            console.log(rst);
            if(rst.status === 200) {
                tip_title = '操作成功';
                msg = rst.msg;
                //$("#balance_"+lottery_type).html(msg);
            } else {
                tip_title = '操作失败';
            }
            //showTips(null, rst.msg, tip_title); # 同步完无需弹框，暂且注释
        },'JSON');
    }

    $('.open-bet-status').click(function () {
        var lottery_type = $(this).attr('lottery_type');
        $('#act-val').val('open-bet-status')
        showTips(lottery_type);
    });

    function showTips(lottery_type, tip_msg = '确定开启本期下注？', title = '提示信息') {
        console.log(lottery_type);
        $('#tip_msg_title').html(title);
        $('#tip_msg').html(tip_msg);
        $('#tipModal').modal('show');
        $("#opConfirm").attr('op-id', lottery_type);
    }

    $('#opConfirm').click(function () {
        var lottery_type = $(this).attr('op-id');
        act = $('#act-val').val();
        if(act == 'init-ds-data'){
            initDsData(lottery_type)
        }else {
            if(lottery_type != null) openBetStatus(lottery_type)
        }
    });

    // 初始化单双数据
    $('.init-ds-data').click(function () {
        var lottery_type = $(this).attr('lottery_type');
        $('#act-val').val('init-ds-data')
        showTips(lottery_type, tmp_msg='确定初始化单双数据？');
    });
})
</script>
