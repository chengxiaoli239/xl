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
use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\RobotUser */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '登陆信息';
$this->params['breadcrumbs'][] = ['label' => 'Robot Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="robot-user-view wrapper site-min-height">
    <div class="row">
        <div class="col-lg-12">
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
                                    #'user_id',
                                    'wcId',
                                    //'wId',
                                    #'uuid',
                                    ['attribute' => 'status','label'=>'账号状态','headerOptions'=>['width'=>'5%'],
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            return ($model->status==1) ? '<strong><font color="green">正常</font></strong>' : '<strong><font color="red">已禁用</font></strong>';
                                        }
                                    ],
                                    ['attribute' => 'wechat_status','label'=>'微信状态','headerOptions'=>['width'=>'5%'],
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            $History = \common\models\eyun\HistoryRobots::findOne(['wcId'=>$model->wcId, 'user_id'=>$model->user_id]);
                                            $txt = '';
                                            if(!empty($History)){
                                                $imgUrl = $History->smallHeadImgUrl;
                                                if(!empty($imgUrl)){
                                                    $txt .= '<img width="30" height="30" src="'.$imgUrl.'">&nbsp;&nbsp;';
                                                }
                                            }
                                            $txt .= ($model->wechat_status==1) ?
                                                '<strong><font color="green">账号在线</font></strong>'
                                                :
                                                '<strong><font color="red">账号离线</font></strong>';
                                            $txt .= '&nbsp;&nbsp;&nbsp;<strong><button id="changeList" class="btn btn-xs btn-danger">更换微信</button></strong>';
                                            return $txt;
                                        }
                                    ],
                                    'desc',
                                    'expire_time:datetime',
                                    #'created_at',
                                    #'updated_at',
                                    'update_at',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode('盘口信息') ?>
            <!--?= Html::a('更新', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?-->
        </header>
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-12">
                    <?= GridView::widget([
                        'dataProvider' => $systemDataProvider,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            'sys_name',
                            [ 'attribute'=>'account','label'=>'系统余额','format'=>'raw',
                                'value'=>function($model){
                                    return "<span id='site_".$model->id."'>".$model->account."</span>";
                                }
                            ],
                            //'balance',
                            [ 'attribute'=>'balance','label'=>'系统余额','format'=>'raw',
                                'value'=>function($model){
                                    return "<span id='balance_".$model->id."'>".$model->balance."</span>";
                                }
                            ],
                            'password',
                            //['attribute'=>'expire_time',//'label'=>'状态',
                            //    'format'=>'raw',
                            //    'value'=>function($model){
                            //        $txt = \backend\service\UserService::accountIsExpireDesc($model->uid, $model->tz_system_id);
                            //        return $txt;
                            //    }
                            //],
                            ['attribute' => 'ssc_domain', 'label'=>'网盘', //'headerOptions' => ['width' => '170'],
                                'value'=> function($model){
                                    return  $model->ssc_domain;
                                },
                            ],
                            [ 'attribute'=>'status','label'=>'账号状态',
                                'format'=>'raw',
                                'value'=>function($model){
                                    return $model->status ? '<font color="green">已激活</font>' : '<font color="red">已禁用</font>';
                                }
                            ],
                            [ 'attribute'=>'is_auto_bet','label'=>'下注开关',
                                'format'=>'raw',
                                'value'=>function($model){
                                    $txt = $model->is_auto_bet ? '<font color="green">已开启</font>' : '<font color="red">已关闭</font>';
                                    $url = '/forum/user/switch-auto-bet-status?id='.$model->id.'&status='.($model->is_auto_bet?0:1);
                                    return Html::a($txt, $url, ['title' => '点击切换','alt'=>'点击切换']);
                                }
                            ],
                            //'desc',
                            [ 'attribute'=>'desc','label'=>'网盘状态',
                                'format'=>'raw',
                                'value'=>function($model){
                                    return empty($model->desc) ? '<font color="green">正常</font>' : '<font color="red">'.$model->desc.'</font>';
                                }
                            ],
                            ['attribute' => 'cookie', 'label'=>'cookie',
                                'format'=>'raw',
                                'value' => function($model) {
                                    $txt = BaseStringHelper::truncate($model->cookie,24);
                                    return Html::a($txt, 'javascript:;', ['title' => $model->cookie,'alt'=>$model->cookie]);
                                }
                            ],
                            [ 'attribute'=>'update_time','label'=>'更新时间','value'=>function($model){
                                return $model->update_time;
                            }],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{update} ', // This will only show the "Update" button
                                'urlCreator' => function ($action, $model, $key, $index) {
                                    if ($action === 'update') {
                                        // Set the URL for the "Update" button
                                        return ['/forum/tz-systems-users/update', 'id' => $key];
                                    } else {
                                        // Use the default URL for other actions
                                        return Url::to([$action, 'id' => $key]);
                                    }
                                },
                                'buttons' => [
                                    'update' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<span class="glyphicon glyphicon-pencil"></span> 更新',
                                            $url,
                                            ['class' => 'btn btn-success btn-xs']
                                        );
                                    },
                                    #'view' => function ($url, $model, $key) {
                                    #    return Html::a(
                                    #        '<span class="glyphicon glyphicon-eye-open"></span> 查看',
                                    #        $url,
                                    #        ['class' => 'btn btn-primary btn-xs']
                                    #    );
                                    #},
                                    #'view-modal' => function ($url, $model, $key) {
                                    #    return Html::a(
                                    #        '<span class="glyphicon glyphicon-eye-open"></span> View',
                                    #        ['/kkkkk/dadsf'], // Use '#' as the href
                                    #        [
                                    #            'class' => 'btn btn-primary',
                                    #            'data-toggle' => 'modal',
                                    #            'data-target' => '#viewModal',
                                    #            'data-url' => Yii::$app->urlManager->createUrl(['view', 'id' => $key]),
                                    #        ]
                                    #    );
                                    #},
                                ],
                            ],
                        ],
                    ]); ?>
                </div>
            </div>
        </div>
    </section>
</section>


<!--提示框-start-->
<div class="modal fade " id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 100%;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">微信列表：</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn', 'headerOptions'=>['width'=>'5%']],

                            #'id',
                            #'wcId',
                            ['attribute' => 'wcId','label'=>'微信', 'headerOptions'=>['width'=>'10%'],
                                'format'=>'raw',
                                'value' => function($model) {
                                    $txt = $model['wcId'] ? '<img width="30px" height="30px" src="'.$model['smallHeadImgUrl'].'">' : '';
                                    return $txt;
                                }
                            ],
                            ['attribute' => 'wechat_status','label'=>'状态', 'headerOptions'=>['width'=>'20%'],
                                'format'=>'raw',
                                'value' => function($model) {
                                    return $model['wcId'] ? '<strong>'.($model['wechat_status']?'<font color="green">在线</font>' : '<font color="gray">离线</font>').'</strong>' : '';
                                }
                            ],
                            ['attribute' => 'nickName','label'=>'昵称', 'headerOptions'=>['width'=>'20%'],
                                'format'=>'raw',
                                'value' => function($model) {
                                    return $model['nickName'];
                                }
                            ],
                            #'update_at:datetime',
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{update}', // This will only show the "Update" button
                                'buttons' => [
                                    'update' => function ($url, $model, $key) {
                                        $offlineTxt = Html::button(
                                            '点击登录<span class="glyphicon glyphicon-arrow-up"></span>',
                                            ['class'=>'btn btn-success btn-xs', 'data-nickname'=>$model['nickName'], 'data-wechatId'=>$model['wcId'], 'data-status'=>0, 'id'=>'change_id_'.$model['id']]
                                        );
                                        $onlineTxt = Html::button(
                                            '点击退出<span class="glyphicon glyphicon-arrow-down"></span>',
                                            ['class'=>'btn btn-danger btn-xs', 'data-nickname'=>$model['nickName'], 'data-wechatId'=>$model['wcId'], 'data-status'=>1, 'id'=>'change_id_'.$model['id']]
                                        );
                                        return !empty($model['wechat_status']) ? $onlineTxt : $offlineTxt;
                                    },
                                ],
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
            <div class="modal-footer">
                <!-- 下方按钮 -->
                <button class="btn btn-warning" id="addNewWechat">登录新微信</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<!--二维码展示框-start-->
<div class="modal fade " id="exampleModal_QrCode" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 100%;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">登录二维码</h4>
            </div>
            <div class="modal-body">
                <div class="row" id="QrCodeImg" style="text-align: center;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>

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

<div class="modal fade" id="tipModalResetProfits" tabindex="-1" role="dialog" aria-labelledby="ModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_profits_title"></h4>
            </div>
            <div class="modal-body">
                <div class="form-group up-reason">
                    <span id="tip_msg_profits"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="opConfirmProfits">确定</button>
            </div>
        </div>
    </div>
</div>


<!--赔率-->
<div class="modal fade" id="setProfitsModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="set_msg_title">设置止盈止损</h4>
            </div>
            <div class="modal-body">
                <form class="bs-example bs-example-form" role="form">
                    <div class="input-group layui-input-inline">
                        <span class="input-group-addon">止盈</span>
                        <input type="text" class="form-control" placeholder="止盈" name="take_profits" id="take_profits" value="<?echo $models[0]->take_profits?>">
                    </div>
                    <br>
                    <div class="input-group layui-input-inline">
                        <span class="input-group-addon">止损</span>
                        <input type="text" class="form-control" placeholder="止损" name="stop_loss" id="stop_loss" value="<?echo $models[0]->stop_loss?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="setProfitsBtn">确定</button>
            </div>
        </div>
    </div>
</div>

<!--网盘跟买-->
<div class="modal fade" id="setFollowModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="set_follow_msg_title">设置跟买</h4>
            </div>
            <div class="modal-body">
                <form class="bs-example bs-example-form" role="form">
                    <div class="input-group layui-input-inline">
                        <span class="input-group-addon">正买账号</span>
                        <input type="text" class="form-control" placeholder="正买账号,多个用英文账号隔开" name="flow_wp_accounts" id="flow_wp_accounts" value="<?echo $models[0]->flow_wp_accounts?>">
                    </div>
                    <br>
                    <div class="input-group layui-input-inline">
                        <span class="input-group-addon">倍数比例</span>
                        <input type="text" class="form-control" placeholder="倍数" name="flow_wp_player_bs" id="flow_wp_player_bs" value="<?echo $models[0]->flow_wp_player_bs?>">
                    </div>
                    <br>
                    <div class="input-group layui-input-inline">
                        <span class="input-group-addon">反买账号</span>
                        <input type="text" class="form-control" placeholder="反买账号,多个用英文账号隔开" name="flow_op_accounts" id="flow_op_accounts" value="<?echo $models[0]->flow_op_accounts?>">
                    </div>
                    <br>
                    <div class="input-group layui-input-inline">
                        <span class="input-group-addon">倍数比例</span>
                        <input type="text" class="form-control" placeholder="倍数" name="flow_op_player_bs" id="flow_op_player_bs" value="<?echo $models[0]->flow_op_player_bs?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="setFollowBtn">确定</button>
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
        // 盈利归零
        $('#opConfirmProfits').click(function () {
            $url = "/forum/user-sys-plans/re-calculate-profits?type=2"; // 归零盈利
            $.get($url, function(rst) {
                console.log(rst)
                if(rst.status === 200) {
                    window.location.href = '/forum/user/view.html';
                }
            },'JSON');
        });

        // 止盈止损
        $('#setProfits').click(function () {
            $('#set_msg_title').html('设置止盈止损');
            $('#setProfitsModal').modal('show');
        });
        // 网盘跟买
        $('#setWpFollow').click(function () {
            //$('#set_follow_msg_title').html('设置跟买');
            $('#setFollowModal').modal('show');
        });

        // 止盈止损
        $('#setProfitsBtn').click(function () {
            profits = $('#take_profits').val()
            loss = $('#stop_loss').val()
            var data = {take_profits:profits, stop_loss:loss};
            $.post("/forum/user/set-profits",data,function(rst) {
                console.log(rst)
                if(rst.status == 200) {
                    window.href.reload()
                }
            },'JSON');
        });

        // 跟买
        $('#setFollowBtn').click(function () {
            flow_wp_accounts = $('#flow_wp_accounts').val()
            flow_wp_player_bs = $('#flow_wp_player_bs').val()
            flow_op_accounts = $('#flow_op_accounts').val()
            flow_op_player_bs = $('#flow_op_player_bs').val()
            var data = {
                flow_wp_accounts:flow_wp_accounts,
                flow_wp_player_bs:flow_wp_player_bs,
                flow_op_accounts:flow_op_accounts,
                flow_op_player_bs:flow_op_player_bs,
            };
            $.post("/forum/user/set-follow-buy",data,function(rst) {
                console.log(rst)
                if(rst.status == 200) {
                    window.href.reload()
                }
            },'JSON');
        });

    });
</script>

<script>
    $(function () {
        historyLists = <?php echo \yii\helpers\Json::encode($historyRecords)?>;
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

        $("#changeList").click(function (rst) {

            $('#exampleModal_msg').modal('show');
        });
    })
</script>




























