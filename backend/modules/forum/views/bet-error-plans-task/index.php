<?php

use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BetErrorPlansTask */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Bet Error Plans Tasks');
$this->params['breadcrumbs'][] = $this->title;
$taskSummary = static function (array $rows) {
    $content = '';
    foreach ($rows as $row) {
        $content .= Html::tag('div',
            Html::tag('span', Html::encode($row[0]), ['class' => 'task-summary-label']).
            Html::tag('span', $row[1], ['class' => 'task-summary-value']),
            ['class' => 'task-summary-line']
        );
    }
    return Html::tag('div', $content, ['class' => 'task-summary']);
};
$taskValue = static function ($value) {
    return trim((string)$value) === '' ? Html::tag('span', '-', ['class' => 'text-muted']) : Html::encode($value);
};
?>
<input type="hidden" id="currentQihao" value="<?php echo $currentQihao;?>">
<section class="bet-error-plans-task-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>

                <?php echo $this->render('_search', [
                    'model' => $searchModel,
                    'plan_ids' => $plan_ids ?? '',
                    'qihao' => $qihao ?? '',
                ]); ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'codes:ntext',
                        //['attribute' => 'codes','label' => '号码',
                        //    'format'=>'raw',
                        //    'value' => function($model) {
                        //        $txt = BaseStringHelper::truncate($model->codes,15);
                        //        return Html::a($txt, 'javascript:;', ['title' => $model->codes,'alt'=>$model->codes]);
                        //    }
                        //],
                        //'uid',
                        //['attribute' => 'uid','label' => 'UID',
                        //    'format'=>'raw',
                        //    'value' => function($model) {
                        //        return $model->uid;
                        //    }
                        //],
                        //'agent_id',
                        //'account',
                        ['attribute' => 'plan_summary','label' => '计划/类型',
                            'contentOptions' => ['class' => 'task-summary-cell'],
                            'format'=>'raw',
                            'value' => function($model) use ($taskSummary, $taskValue) {
                                return $taskSummary([
                                    ['计划', $taskValue($model->plan_id.'_'.$model->bet_sort_key)],
                                    ['类型', $taskValue(\backend\service\BetService::getTypeNameByTzType($model->tz_type))],
                                ]);
                            }
                        ],
                        //'bet_url:url',
                        //'bet_headers',
                        //'post_datas:ntext',
                        //['attribute' => 'post_datas','label' => '请求内容',
                        //    'format'=>'raw',
                        //    'value' => function($model) {
                        //        $txt = BaseStringHelper::truncate($model->post_datas,15);
                        //        $opions = [
                        //            'class' => 'act-post-desc',
                        //            'title' => $model->post_datas,
                        //            'alt'=>$model->post_datas,
                        //            'data-url' => $model->bet_url,
                        //            'data-content' => $model->post_datas,
                        //            'data-error' => $model->error_desc,
                        //        ];
                        //        return Html::a($txt, 'javascript:;', $opions);
                        //    }
                        //],
                        //'playway',
                        //['attribute'=>'playway','label'=>'类型',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                        //    'format'=>'raw',
                        //    'value'=>function($model){
                        //        return \backend\service\FilterEnumeRateService::getPlayWayTxt($model->playway);
                        //    },
                            //'filter' => \backend\service\FilterEnumeRateService::getPlayWays()
                        //],
                        //'tz_type',
                        //'playway_name',
                        //'bet_money',
                        ['attribute' => 'amount_period','label' => '金额/期号',
                            'contentOptions' => ['class' => 'task-summary-cell'],
                            'format'=>'raw',
                            'value' => function($model) use ($taskSummary, $taskValue) {
                                return $taskSummary([
                                    ['金额', $taskValue('['.$model->single.'元]'.$model->bet_money)],
                                    ['期号', $taskValue($model->qihao)],
                                ]);
                            }
                        ],
                        ['attribute' => 'result_status','label' => '结果/状态',
                            'contentOptions' => ['class' => 'task-summary-cell'],
                            'format'=>'raw',
                            'value' => function($model) use ($taskSummary) {
                                $txt = BaseStringHelper::truncate($model->post_desc,15);
                                $opions = [
                                    'class' => 'act-post-desc',
                                    //'title' => $model->post_datas,
                                    'alt'=>$model->post_datas,
                                    'data-url' => $model->bet_url,
                                    'data-content' => $model->post_datas,
                                    'data-error' => $model->post_desc,
                                ];
                                $result = Html::a((!$model->status)?'<strong><font color="#696969">等待推送</font></strong>':$txt, 'javascript:;', $opions);
                                $options = ['title' => '更新状态'.$model->status];
                                if($model->status==2){
                                    $status = '<strong><font color="green">推送成功</font></strong>';
                                }elseif($model->status == 3){
                                    $status = '<strong><font color="red">推送失败</font></strong>';
                                    $options['class'] = 'act-re-bet';
                                    $options['data-rebet-url'] = "/forum/bet-error-plans-task/switch-status";
                                    $options['data-id'] = $model->id;
                                    $options['data-qihao'] = $model->qihao;
                                    $options['id'] = 'act_'.$model->id;
                                }elseif($model->status == 4){
                                    $status = '<strong><font color="red">推送超时</font></strong>';
                                }else{
                                    $status = '<strong><font color="#696969">等待推送</font></strong>';
                                }
                                return $taskSummary([
                                    ['结果', $result],
                                    ['状态', Html::a($status, 'javascript:;', $options)],
                                ]);
                            }
                        ],
                        ['attribute' => 'execution_summary','label' => '下注时间',
                            'contentOptions' => ['class' => 'task-summary-cell'],
                            'format'=>'raw',
                            'value' => function($model) use ($taskSummary, $taskValue) {
                                $finished = $model->bet_finished_at ? date('m-d H:i:s', $model->bet_finished_at) : '-';
                                if($model->bet_finished_at && $model->bet_started_at){
                                    $finished .= '<br><small>耗时 '.max(0, $model->bet_finished_at - $model->bet_started_at).' 秒</small>';
                                }
                                return $taskSummary([
                                    ['开始', $taskValue($model->bet_started_at ? date('m-d H:i:s', $model->bet_started_at) : '-')],
                                    ['结束', $finished === '-' ? $taskValue($finished) : $finished],
                                ]);
                            }
                        ],
                        /*
                        ['attribute' => 'bet_finished_at','label' => '下注结束',
                            'format'=>'raw',
                            'value' => function($model) {
                                if(!$model->bet_finished_at){ return '-'; }
                                $duration = $model->bet_started_at ? max(0, $model->bet_finished_at - $model->bet_started_at) : null;
                                return date('m-d H:i:s', $model->bet_finished_at).($duration === null ? '' : '<br><small>耗时 '.$duration.' 秒</small>');
                            }
                        ],
                        */
                        //'sn',
                        //'snid',
                        /*
                        ['attribute' => 'snid','label' => '单号',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->snid;
                            }
                        ],
                        */
                        //'plan_id',
                        //'tz_system_id',
                        //'lotteryclass',
                        ['attribute' => 'record_summary','label' => '种类/时间',
                            'contentOptions' => ['class' => 'task-summary-cell'],
                            'format'=>'raw',
                            'value' => function($model) use ($taskSummary, $taskValue) {
                                return $taskSummary([
                                    ['种类', $taskValue(\backend\service\BetService::getLotteryName($model->lottery_type))],
                                    ['记录', $taskValue(date('m-d H:i', $model->created_at))],
                                ]);
                            }
                        ],
                        //'post_desc',
                        //'error_desc',
                        //'updated_time',
                        //'updated_at',
                        /* ['attribute' => 'created_at','label' => '时间',
                            'format'=>'raw',
                            'value' => function($model) {
                                return date('m-d H:i', $model->created_at);
                            }
                        ], */

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<?php $this->registerCss(<<<'CSS'
.bet-error-plans-task-index .grid-view {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.bet-error-plans-task-index .grid-view th,
.bet-error-plans-task-index .grid-view td {
    vertical-align: top;
}
.bet-error-plans-task-index .task-summary-cell {
    min-width: 132px;
}
.bet-error-plans-task-index .task-summary {
    line-height: 1.55;
    min-width: 112px;
}
.bet-error-plans-task-index .task-summary-line {
    display: flex;
    align-items: flex-start;
    gap: 4px;
    white-space: normal;
}
.bet-error-plans-task-index .task-summary-label {
    color: #888;
    flex: 0 0 38px;
    white-space: nowrap;
}
.bet-error-plans-task-index .task-summary-value {
    min-width: 0;
    white-space: normal;
    overflow-wrap: anywhere;
}
@media (max-width: 767px) {
    .bet-error-plans-task-index .panel-body {
        padding: 10px;
    }
    .bet-error-plans-task-index .grid-view table {
        min-width: 700px;
        margin-bottom: 0;
        font-size: 12px;
    }
    .bet-error-plans-task-index .grid-view th,
    .bet-error-plans-task-index .grid-view td {
        padding: 6px 5px;
    }
    .bet-error-plans-task-index .task-summary-cell {
        min-width: 142px;
        max-width: 220px;
    }
}
CSS
); ?>
<!--提示框-start-->
<div class="modal fade " id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 800px;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>推送结果：</strong>
                    <pre><code id="rst_code"></code></pre>
                    <strong>推送内容：</strong>
                    <pre><code id="push_content"></code></pre>
                </form>
            </div>
            <!--div class="form-group down-reason">
                <p><label>备注信息:</label><input class="form-control" id="message" name="message" /></p>
            </div-->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<!--提示框-end-->

<!--提示框-->
<div class="modal fade" id="MSG_TipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="del_tip_msg_title"></h4>
            </div>
            <div class="modal-body">
                <div class="form-group up-reason">
                    <label id="del_tip_msg" for="del_tip_msg"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="actConfirm">确定</button>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="act">
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
$(function () {
    //$("[id^='act-post-desc']").click(function (rst) {
    $(".act-post-desc").click(function (rst) {
        bet_rst = $(this).data('error');
        content = $(this).data('content');

        act_data = {"bet_url":$(this).data('url'), "bet_content":content};
        $('#rst_code').text(JSON.stringify(bet_rst,null,' '))
        $('#push_content').text(JSON.stringify(act_data,null,' '))

        $('#exampleModal_msg').modal('show');
    });

    // 删除用户
    $('.act-re-bet').click(function () {
        $("#actConfirm").attr('data-id', $(this).attr('data-id'));
        console.log($(this).attr('data-id'));

        $("#del_tip_msg_title").html("温馨提示");
        re_bet_qihao = $(this).data('qihao');
        current_qihao = $("#currentQihao").val();
        console.log(re_bet_qihao, current_qihao)
        if(re_bet_qihao != current_qihao){
            $("#del_tip_msg").html("<font color='red'><strong>已关盘或者未开盘的期号不能重置推送状态</strong></font>");
            $("#act").val('');
        }else {
            $("#del_tip_msg").html("确定重新下注失败号码？");
            $("#act").val('act-rebet');
        }
        $("#MSG_TipModal").modal('show');
    });

    $("#actConfirm").click(function () {
        url = $("#act_"+$(this).data('id')).data('rebet-url')
        if($("#act").val() != ''){
            $.post(url, {id:$(this).data('id')}, function(rst) {
                if(rst.status == 200) {
                    $("#act").val("");
                    $("#del_tip_msg").html("重置状态成功，等待推送下注...");
                    console.log(rst.msg)
                    $("#MSG_TipModal").modal('show');
                } else {
                    tip_title = '操作失败';
                }
            },'JSON');
        }else {
            $("#MSG_TipModal").hide();
            document.location.reload();//当前页面
        }

    });
});
</script>
