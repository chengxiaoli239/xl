<?php

use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BetErrorPlansTask */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Bet Error Plans Tasks');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="bet-error-plans-task-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

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
                        ['attribute' => 'account','label' => '账号',
                            'format'=>'raw',
                            'value' => function($model) {
                                //return $model->account."[".$model->uid."]";
                                return Html::a($model->account, '/forum/bet-error-plans-task/index?BetErrorPlansTask[account]='.$model->account)."[".$model->uid."]";
                            }
                        ],
                        ['attribute' => 'plan_id','label' => 'planid',
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::a($model->plan_id.'_'.$model->bet_sort_key, '/forum/bet-error-plans-task/index?BetErrorPlansTask[plan_id]='.$model->plan_id);
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
                        ['attribute' => 'tz_type','label' => '类型',
                            'format'=>'raw',
                            'value' => function($model) {
                                return \backend\service\BetService::getTypeNameByTzType($model->tz_type);
                            }
                        ],
                        //'playway_name',
                        //'bet_money',
                        ['attribute' => 'bet_money','label' => '金额',
                            'format'=>'raw',
                            'value' => function($model) {
                                return '['.$model->single.'元]'.$model->bet_money;
                            }
                        ],
                        //'single',
                        //'qihao',
                        ['attribute' => 'qihao','label'=>'期号',
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::a($model->qihao, '/forum/bet-error-plans-task/index?BetErrorPlansTask[qihao]='.$model->qihao);
                            }
                        ],
                        ['attribute' => 'post_desc','label' => '结果',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->post_desc,15);
                                $opions = [
                                    'class' => 'act-post-desc',
                                    //'title' => $model->post_datas,
                                    'alt'=>$model->post_datas,
                                    'data-url' => $model->bet_url,
                                    'data-content' => $model->post_datas,
                                    'data-error' => $model->post_desc,
                                ];
                                return Html::a($txt, 'javascript:;', $opions);
                            }
                        ],
                        //'kj_codes',
                        //'status',
                        ['attribute' => 'status','label' => '状态',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->status == 2 ? '<font color="green">推送成功</font>' : ($model->status == 3 ? '<font color="red">推送失败</a>' : ($model->status==4?'<font color="red">推送超时</a>':'<font color="#696969">等待推送</a>'));
                                //$url = "/forum/user-custom-plans/update-status?id=".$model->id;
                                return Html::a('<strong>'.$txt.'</strong>['.$model->id.']&nbsp;&nbsp;<span id="re_set_'.$model->id.'" data-rid="'.$model->id.'">重置</span>', 'javascript:;', ['title' => '更新状态'.$model->status]);
                            }
                        ],
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
                        //'lottery_type',
                        ['attribute' => 'lottery_type','label' => '种类',
                            'format'=>'raw',
                            'value' => function($model) {
                                return \backend\service\BetService::getLotteryName($model->lottery_type);
                            }
                        ],
                        //'post_desc',
                        //'error_desc',
                        //'updated_time',
                        //'updated_at',
                        //'created_at',
                        ['attribute' => 'created_at','label' => '时间',
                            'format'=>'raw',
                            'value' => function($model) {
                                $formattedDate = date('m-d H:i', $model->created_at);
                                $fullDate = date('Y-m-d H:i:s', $model->created_at); // 例如，完整时间戳
                                return "<span title='{$fullDate}'>{$formattedDate}</span>";
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
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

    $("[id^=re_set]").click(function(){
        Ewin.confirm({ message: '重置状态，有可能会再次下注'}).on(function (e) {
            rid = $(this).data('rid');
            console.log(rid)
            data = {rid:rid}
            url = '/forum/bet-error-plans-task/switch-task-status'
            $.post(url, data, function(rst) {
                message = (rst.status) == 200 ? '重置成功' : rst.msg;
                Ewin.confirm({ message: message}).on(function (e) {});
            });
        });
    });
});
</script>
