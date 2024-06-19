<link rel="stylesheet" href="/vendors/layui/2.5.4/css/layui.css?v=2020">
<link rel="stylesheet" href="/css/layui/global.css?v={{STATIC_VERSION}}">
<script type="text/javascript" src="/vendors/layui-layer/3.1.1/layer.js"></script>
<script type="text/javascript" src="/vendors/layui/2.4.5/layui.js"></script>
<script type="text/javascript" src="/vendors/atrtemplate/4.13.2/template-web.js"></script>
<script type="text/javascript" src="/statics/js/jquery-2.0.3.js"></script>
<script type="text/javascript" src="/js/layui/global.js?v={{STATIC_VERSION}}"></script>
<script type="text/javascript" src="/js/common.js?v={{STATIC_VERSION}}"></script>
<?php

use backend\service\agent\AgentService;
use yii\bootstrap\Modal;
use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\RobotUser */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '个人信息';
$this->params['breadcrumbs'][] = ['label' => 'Robot Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$js=<<<'JS'
    $('.edit-btn').click(function(){
        var url = $(this).attr('data-url');
        console.log(url);
        $('#tz-system-user-modal .modal-content').load(url, function() {
            $('#tz-system-user-modal').modal('show');
        });
    });
JS;
$this->registerJs($js);

?>
<style>
    /* 默认的弹框大小 */
    .modal-lg {
        width: 65%;
        height: 30%;
        margin: 100px auto;
    }

    /* 在小屏幕上设置较大的弹框大小 */
    @media (max-width: 768px) {
        .modal-lg {
            width: 90%;
            height: 30%;
            margin: 50px auto;
        }
    }
</style>
<section class="robot-user-view wrapper site-min-height">
    <div class="row">
        <div class="col-lg-10">
            <section class="panel">
                <header class="panel-heading">
                    <?= Html::encode($this->title) ?>
                    <!--?= Html::a('更新', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?-->
                </header>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-lg-11">
                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => [
                                    #'id',
                                    'sys_name',
                                    [ 'attribute'=>'account','label'=>'盘口账号','format'=>'raw',
                                        'value'=>function($model){
                                            return "<span id='site_".$model->id."'>".$model->account."</span>";
                                        }
                                    ],
                                    [ 'attribute'=>'password','label'=>'盘口密码','format'=>'raw',
                                        'value'=>function($model){
                                            return $model->password;
                                        }
                                    ],
                                    ['attribute' => 'ssc_domain', 'label'=>'网盘', //'headerOptions' => ['width' => '170'],
                                        'format'=>'raw',
                                        'value'=> function($model){
                                            return  $model->ssc_domain.' <strong><font color="green">['.$model->kj_num.'位]</font></strong>';
                                        },
                                    ],
                                    ['attribute' => 'secure_code', 'label'=>'安全码', //'headerOptions' => ['width' => '170'],
                                        'value'=> function($model){
                                            return  $model->secure_code;
                                        },
                                    ],
                                    ['attribute' => 'status','label'=>'状态','headerOptions'=>['width'=>'5%'],
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            $url0 = "/wechat/robot-user/switch-status?id=".$model->id.'&status=1'; # 点击激活
                                            $url1 = "/wechat/robot-user/switch-status?id=".$model->id.'&status=0'; # 点击禁用
                                            $txt = '<strong>'.\common\helpers\Platform::SYSTEM_STATUS_OPTIONS[$model->status].'</strong>';
                                            if($model->status == 1){
                                                $txt = "<font color='green'>{$txt}</font>";
                                                return Html::a($txt, $url1, ['title' => '点击"停止工作"']).'<i class="icon-refresh"></i>';
                                            }
                                            if(!$model->status){
                                                $txt = "<font color='red'>{$txt}</font>";
                                                return Html::a($txt, $url0, ['title' => '点击开启工作']).'<i class="icon-refresh"></i>';
                                            }
                                        }
                                    ],
                                    //'balance',
                                    [ 'attribute'=>'balance','label'=>'描述情况','format'=>'raw',
                                        'value'=>function($model){
                                            list($balance, $todayPl, $todayBet, $weekBet, $weekPl, $lastWeekBet, $lastWeekPl) = AgentService::getCalcMoney($model->uid, $siteInfo=0);
                                            return "余额：<span id='balance_".$model->id."'>".$model->balance."</span>、".'今日盈亏：'.$todayPl.'、有效金额：'.$todayBet;
                                        }
                                    ],
                                    'expire_time:datetime',
                                    ['attribute' => 'desc','label'=>'操作','headerOptions'=>['width'=>'5%'],
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            return Html::a('修改盘口 <span class="glyphicon glyphicon-pencil"></span>', 'javascript:void(0);', [
                                                'class' => 'edit-btn btn btn-xs edit-button',
                                                'data-url' => Yii::$app->urlManager->createUrl(['wechat/robot-user/update-site-info', 'id' => $model->id]),
                                            ]);
                                        }
                                    ],
                                    'update_time',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
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

<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
    $(function () {
        function updateBalance(id) {
            var data = {tz_system_user_id:id};
            var tip_title = '';
            $.post("/forum/user/sync-one-balance",data,function(rst) {
                if(rst.status == 200) {
                    tip_title = '操作成功';
                    balance = rst.balance;
                    $("#balance_"+id).html(balance);
                } else {
                    tip_title = '操作失败';
                }
                //showTips(null, rst.msg, tip_title); # 同步完无需弹框，暂且注释
            },'JSON');
        }

        $('.update-balance').click(function () {
            var balanceId = $(this).attr('id');
            showTips(balanceId);
        });

        function showTips(id, tip_msg = '同步余额', title = '提示信息') {
            console.log(id);
            $('#tip_msg_title').html(title);
            $('#tip_msg').html(tip_msg);
            $('#tipModal').modal('show');
            $("#opConfirm").attr('op-id', id);
        }

        $('#opConfirm').click(function () {
            var id = $(this).attr('op-id');
            if(id != null) updateBalance(id)
        });

        $('#resetProfits').click(function () {
            $('#tip_msg_profits_title').html('利润归零');
            $('#tip_msg_profits').html('确定归零所有盈利')
            $('#tipModalResetProfits').modal('show');
        });
    });
</script>

<script>
$(function () {
    $(document).on('click', '.update-link, .edit-btn', function() {
        var url = $(this).data('url');
        // 根据点击的链接执行不同的操作
        // 你可以使用url来发送异步请求
    });
})
</script>


<!-- 模态框 -->
<?php Modal::begin([
    'id' => 'tz-system-user-modal',
    'size' => 'modal-lg',
]); ?>
<?php Modal::end(); ?>


<?php Modal::begin([
    'id' => 'update-password-modal',
    'size' => 'modal-md',
    'header' => '<h4 class="modal-title">修改密码</h4>',
]); ?>
<div id="update-password-content">
<?php Modal::end(); ?>
<script>
$(function (){
    $('#updatePassword').on('click', function () {
        $('#update-password-modal').modal('show');
        $('#update-password-content').load("/forum/user/act-update-password-page");
    });
})
</script>