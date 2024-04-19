<link rel="stylesheet" href="/vendors/layui/2.5.4/css/layui.css?v=2020">
<link rel="stylesheet" href="/css/layui/global.css?v={{STATIC_VERSION}}">
<script type="text/javascript" src="/vendors/layui-layer/3.1.1/layer.js"></script>
<script type="text/javascript" src="/vendors/layui/2.4.5/layui.js"></script>
<script type="text/javascript" src="/vendors/atrtemplate/4.13.2/template-web.js"></script>
<script type="text/javascript" src="/statics/js/jquery-2.0.3.js"></script>
<script type="text/javascript" src="/js/layui/global.js?v={{STATIC_VERSION}}"></script>
<script type="text/javascript" src="/js/common.js?v={{STATIC_VERSION}}"></script>
<?php

use izyue\admin\widgets\GridView;
use izyue\admin\widgets\ListView;
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
                                    [ 'attribute'=>'account','label'=>'账号','format'=>'raw',
                                        'value'=>function($model){
                                            return "<span id='site_".$model->id."'>".$model->account."</span>";
                                        }
                                    ],
                                    'password',
                                    ['attribute' => 'status','label'=>'账号状态','headerOptions'=>['width'=>'5%'],
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            return ($model->status==1) ? '<strong><font color="green">正常</font></strong>' : '<strong><font color="red">已禁用</font></strong>';
                                        }
                                    ],
                                    ['attribute' => 'ssc_domain', 'label'=>'网盘', //'headerOptions' => ['width' => '170'],
                                        'value'=> function($model){
                                            return  $model->ssc_domain;
                                        },
                                    ],
                                    //'balance',
                                    [ 'attribute'=>'balance','label'=>'余额','format'=>'raw',
                                        'value'=>function($model){
                                            return "<span id='balance_".$model->id."'>".$model->balance."</span>";
                                        }
                                    ],
                                    'desc',
                                    'expire_time:datetime',
                                    ['attribute' => 'desc','label'=>'操作','headerOptions'=>['width'=>'5%'],
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            return '<a href="javascript:;" id="updateSiteInfo">修改盘口</a>'.
                                                Html::a('<span class="glyphicon glyphicon-pencil"></span>', 'javascript:void(0);', [
                                                    'class' => 'edit-btn btn btn-xs edit-button',
                                                    'data-url' => Yii::$app->urlManager->createUrl(['wechat/robot-user/update-site-info', 'id' => $model->id]),
                                                ]);
                                        }
                                    ],
                                    #'created_at',
                                    #'updated_at',
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
        historyLists = <?php if (isset($historyRecords)) {
            echo \yii\helpers\Json::encode($historyRecords);
        }?>;
        function switchWechat(wechatId, switchStatus, nickName='') {
            var data = {wechatId:wechatId, switchStatus:switchStatus, 'nickName':nickName};
            if(switchStatus===0 && wechatId !== ''){
                // 弹出确认对话框
                layer.confirm('您确定下线该微信 <strong><font color="green">'+nickName+'</font><strong> ？', {
                    icon: 3,   // 设置对话框图标（3代表警告）
                    title: '确认操作',  // 设置对话框标题
                    btn: ['确定', '取消'],  // 自定义按钮文本，可以根据需要修改
                }, function(){
                    // 用户点击"确定"按钮后执行的回调函数
                    // 在这里可以编写确认操作的代码
                    //layer.msg('操作已执行', {icon: 1});  // 弹出消息提示
                    actSwitchStatus(data)
                }, function(){
                    // 用户点击"取消"按钮后执行的回调函数
                    //layer.alert('操作已取消', {icon: 2});  // 弹出消息提示
                });
            }else {
                // 获取二维码登录
                actSwitchStatus(data)
            }
        }

        function actSwitchStatus(data){
            var tip_title = '';
            $.post("/wechat/robot-user/switch-wechat",data,function(rst) {
                console.log(rst);
                switchStatus = data.switchStatus
                wechatId = data.wechatId
                if(rst.status === 200) {
                    tip_title = '操作成功';
                    msg = rst.msg;
                    //$("#open_bet_status_"+id).html(msg);
                    if(switchStatus){
                        // 获取二维码
                        wId = rst.data.wId   // 登录实例
                        qrCodeUrl = rst.data.qrCodeUrl // 二维码图片  exampleModal_QrCode
                        imgDiv = '<img src="'+qrCodeUrl+'" height="250px;" width="250px;">'
                        $('#QrCodeImg').html(imgDiv)
                        $('#exampleModal_QrCode').modal('show');

                        setTimeout(function () {
                            console.log('5秒后开始进入')
                            $.post("/wechat/robot-user/act-wechat-login",{wId:wId, 'wcId':wechatId},function(loginRst) {
                                if(loginRst.status === 200) {
                                    d = loginRst.data
                                    var nickName = d.nickName ? d.nickName : '';
                                    // 返回成功刷新网页
                                    layer.msg(nickName + ' 登录成功', {icon: 1});
                                    // 嵌套的 setTimeout
                                    setTimeout(function () {
                                        location.reload();
                                    }, 2000); // 2秒的延迟
                                }
                            });
                        }, 5000)
                    }else {
                        location.reload();
                    }
                } else {
                    //Ewin.alert(rst.msg, );
                    console.log(rst)
                    layer.msg(rst.msg, {icon: 7});
                    if(rst.status<40000){
                        console.log('dddd')
                        setTimeout(function () {
                            location.reload();
                        }, 2000); // 2秒的延迟
                    }
                }
            },'JSON');
        }

        // 列表微信切换登录货下线操作
        $(document).on('click', '[id^="change_id_"]', function () {
            var wechatId = $(this).attr('data-wechatid');
            var nowStatus = $(this).attr('data-status');
            var nickName = $(this).attr('data-nickname');
            switchStatus = (nowStatus==1) ? 0 : 1
            console.log(wechatId, nowStatus);
            switchWechat(wechatId, switchStatus, nickName)
        });

        $(document).on('click', '[id="addNewWechat"]', function () {
            var wechatId = ''
            switchStatus = 1
            switchWechat(wechatId, switchStatus)
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




























