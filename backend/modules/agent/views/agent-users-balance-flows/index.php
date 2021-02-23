<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\AgentUsersBalanceFlows */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Agent Users Balance Flows');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="agent-users-balance-flows-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Agent Users Balance Flows'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

    <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'agent_id',
                        //'member_id',
                        'member_account',
                        //'type',
                        ['attribute'=>'type','label'=>'类型',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return \backend\service\AgentUsersService::getFlowTypeTxt($model->type);
                            },
                            'filter' => \backend\service\AgentUsersService::getFlowtypes(),
                        ],
                        'balance',
                        'balance_now',
                        'balance_after',
                        'desc',
                        //'status',
                        ['attribute'=>'status','label'=>'操作',//'headerOptions'=>['width'=>'5%'],// #'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                if($model->status == 0){ # 未审核
                                    $url1 = "/agent/agent-users-balance-flows/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                                    $url2 = "/agent/agent-users-balance-flows/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                                    //$txt = "<font color='blue'>待审核</font>" ;
                                    //$txt1 =  Html::button($txt, $url1, ['title' => '点击审核']);
                                    $options1 = [
                                        'type'=>'button',
                                        'data-type'=>$model->type,
                                        'data-id' => $model->id,
                                        'data-balance' => $model->balance,
                                        'data-name' => $model->member_account,
                                        'class'=>'min-btn btn-success act-check btn',
                                        'data-status' => 1,
                                    ];
                                    $txt1 = Html::button('通过', $options1);

                                    $options2 = [
                                        'type'=>'button',
                                        'data-type'=>$model->type,
                                        'data-id' => $model->id,
                                        'data-balance' => $model->balance,
                                        'data-name' => $model->member_account,
                                        'class'=>'min-btn btn-danger act-check btn',
                                        'data-status' => 2,
                                    ];
                                    $txt2 = Html::button('拒绝', $options2);

                                    return $txt1 ."&nbsp;&nbsp;". $txt2;
                                }
                                if($model->status == 1){
                                    $txt = "<font color='green'>审核通过 √</font>" ;
                                    return Html::a($txt, "#", ['title' => '审核通过']);
                                }
                                if($model->status == 2){
                                    $txt = "<font color='red'>已拒绝 X</font>";
                                    return Html::a($txt, "#", ['title' => '已拒绝']);
                                }
                                return '';
                            }
                        ],
                        //'created_at',
                        ['attribute'=>'created_at','label'=>'申请时间',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return date('Y-m-d H:i:s', $model->created_at);
                            }
                        ],
                        ['attribute'=>'created_at','label'=>'审核时间',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->check_time ? date('Y-m-d H:i:s', $model->check_time) : "";
                            }
                        ],
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<input type="hidden" id="check_id" name="check_id" value="">
<input type="hidden" id="check_status" name="check_status" value="">
<!--修改结果提示-->
<div class="modal fade" id="rstTipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title"></h4>
            </div>
            <div class="modal-body">
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

<!--处理提示框-->
<div class="modal fade" id="checkTipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="check_tip_msg_title"></h4>
            </div>
            <div class="modal-body">
                <div class="form-group up-reason">
                    <label id="check_tip_msg" for="check_tip_msg"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="checkConfirm">确定</button>
            </div>
        </div>
    </div>
</div>

<!--script src="https://cdn.bootcss.com/jquery/2.0.3/jquery.js"></script-->
<script src="/statics/datetimepicker/jquery.js"></script>
<script>
    $(function () {
        $(".act-check").click(function () {
            var id = $(this).attr('data-id');
            var type_desc = $(this).attr('data-type') == 1 ? '上' : '扣除';
            var status = $(this).attr('data-status');
            var account = $(this).attr('data-name');
            var balance = $(this).attr('data-balance');
            var rst_msg = status == 1 ? '通过' : '拒绝';
            console.log();
            $("#check_id").val(id);
            $("#check_status").val(status);

            $("#check_tip_msg_title").html("提示信息：");
            $("#check_tip_msg").html(rst_msg + ': 用户 '+ account +', 申请' + type_desc + balance + '积分');
            $("#checkTipModal").modal('show');
        });

        $("#checkConfirm").click(function () {
            var id = $("#check_id").val();
            var status = $("#check_status").val();
            data = {id:id, status:status, type:1};
            $.post("/agent/agent-users-balance-flows/user-flows-check",data,function(rst) {
                console.log(rst);
                if (rst.status == 200) {
                    $("#tip_msg_title").html('处理结果')
                    $("#tip_msg_rst").html(rst.msg)
                    $("#rstTipModal").modal('show');
                }
            });
        });

        $("#opRstConfirm").click(function () {
            window.location.href = '/agent/agent-users-balance-flows/index';
        });
    });
</script>
