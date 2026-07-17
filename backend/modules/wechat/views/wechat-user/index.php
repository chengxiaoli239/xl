<link rel="stylesheet" href="/vendors/layui/2.5.4/css/layui.css?v=2020">
<link rel="stylesheet" href="/css/layui/global.css?v={{STATIC_VERSION}}">
<script type="text/javascript" src="/vendors/layui-layer/3.1.1/layer.js"></script>
<script type="text/javascript" src="/vendors/layui/2.4.5/layui.js"></script>
<script type="text/javascript" src="/vendors/atrtemplate/4.13.2/template-web.js"></script>
<script type="text/javascript" src="/statics/js/jquery-2.0.3.js"></script>
<script type="text/javascript" src="/js/layui/global.js?v={{STATIC_VERSION}}"></script>
<script type="text/javascript" src="/js/common.js?v={{STATIC_VERSION}}"></script>
<?php

use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\wechat\WechatUser */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '客户列表';
$this->params['breadcrumbs'][] = $this->title;
$columns = array_merge(
    [
        ['class' => 'yii\grid\CheckboxColumn', 'headerOptions'=>['width'=>'2%']],

        //'id',
        //'user_id',
        //'userName',
        ['attribute' => 'smallHead', 'label'=>'头像','headerOptions'=>['width'=>'3%'], // 图片字段的属性
            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
            'value' => function ($model) {
                return Html::img($model->smallHead, ['width' => '30px']);
            },
        ],

    ],
    !$is3dAdmin ? [] :
    [
        ['attribute' => 'user_id', 'label'=>'代理', //'headerOptions' => ['width' => '5%'],
            'format' => 'raw',
            'value'=> function($model){
                return $model->proxy->username;
            },
        ],
    ],
    [
        ['attribute' => 'nickName','label'=>'昵称','headerOptions'=>['width'=>'10%'], //'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value' => function($model) {
                return BaseStringHelper::truncate($model->nickName,10);
            }
        ],
        ['attribute' => 'userName', 'label'=>'平台ID','headerOptions'=>['width'=>'3%'], // 图片字段的属性
            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
            'value' => function ($model) {
                return $model->userName;
            },
        ],
        ['attribute'=>'balance', 'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'contentOptions' => function($model){
                return ['id' => 'balance_'.$model->id];
            },
            'format'=>'raw',
            'value'=>function($model){
                return $model->balance;
            }
        ],
        ['attribute'=>'status','label'=>'状态','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=status&val=1'; # 点击开启
                $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=status&val=0'; # 点击关闭
                if($model->status == 1){
                    $txt = "<font color='green'>√</font>" ;
                    return Html::a($txt, $url1, ['title' => '点击关闭消息接收', 'alt'=>'点击关闭消息接收']);
                }
                if(!$model->status){
                    $txt = "<font color='red'>X</font>";
                    return Html::a($txt, $url0, ['title' => '点击开启消息接收', 'alt'=>'点击开启消息接收']);
                }
                return '';
            }
        ],
        //['attribute'=>'is_tuo', 'headerOptions'=>['width'=>'5%'], // 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
        //    'format'=>'raw',
        //    'value'=>function($model){
        //        $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_tuo&val=1'; # 点击开启
        //        $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_tuo&val=0'; # 点击关闭
        //        if($model->is_tuo == 1){
        //            $txt = "<font color='green'>√</font>" ;
        //            return Html::a($txt, $url1, ['title' => '点击关闭']);
        //        }
        //        if(!$model->is_tuo){
        //            $txt = "<font color='red'>X</font>";
        //            return Html::a($txt, $url0, ['title' => '点击开启']);
        //        }
        //        //return $model->snid;
        //    }
        //],
        //'is_chi',
        ['attribute'=>'is_chi','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_chi&val=1'; # 点击开启
                $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_chi&val=0'; # 点击关闭
                if($model->is_chi == 1){
                    $txt = "<font color='green'>√</font>" ;
                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                }
                if(!$model->is_chi){
                    $txt = "<font color='red'>X</font>";
                    return Html::a($txt, $url0, ['title' => '点击开启']);
                }
                return '';
            }
        ],
        //'is_cha',
        //['attribute'=>'is_private','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
        //    'format'=>'raw',
        //    'value'=>function($model){
        //        $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_private&val=1'; # 点击开启
        //        $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_private&val=0'; # 点击关闭
        //        if($model->is_private == 1){
        //            $txt = "<font color='green'>√</font>" ;
        //            return Html::a($txt, $url1, ['title' => '点击关闭']);
        //        }
        //        if(!$model->is_private){
        //            $txt = "<font color='red'>X</font>";
        //            return Html::a($txt, $url0, ['title' => '点击开启']);
        //        }
        //        //return $model->snid;
        //    }
        //],
        //['attribute'=>'is_cha','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
        //    'format'=>'raw',
        //    'value'=>function($model){
        //        $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_cha&val=1'; # 点击开启
        //        $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_cha&val=0'; # 点击关闭
        //        if($model->is_cha == 1){
        //            $txt = "<font color='green'>√</font>" ;
        //            return Html::a($txt, $url1, ['title' => '点击关闭']);
        //        }
        //        if(!$model->is_cha){
        //            $txt = "<font color='red'>X</font>";
        //            return Html::a($txt, $url0, ['title' => '点击开启']);
        //        }
        //        return '';
        //    }
        //],
    ],
    $lottery_type==\common\helpers\LotteryType::AZ_LUCKY_5?[]:
    [
        ['attribute'=>'is_need_confirm','label'=>'需确认','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_need_confirm&val=1'; # 点击开启
                $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_need_confirm&val=0'; # 点击关闭
                if($model->is_need_confirm == 1){
                    $txt = "<font color='green'>√</font>" ;
                    return Html::a($txt, $url1, ['title' => '需确认才上盘口']);
                }
                if(!$model->is_need_confirm){
                    $txt = "<font color='red'>X</font>";
                    return Html::a($txt, $url0, ['title' => '无需确认上盘口']);
                }
                return '';
            }
        ],
        ['attribute'=>'reply_type','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=reply_type&val=1'; # 点击开启
                $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=reply_type&val=0'; # 点击关闭
                if($model->reply_type == 1){
                    $txt = "<font color='green'>√</font>" ;
                    return Html::a($txt, $url1, ['title' => '打包回']);
                }
                if(!$model->reply_type){
                    $txt = "<font color='red'>X</font>";
                    return Html::a($txt, $url0, ['title' => '即时回']);
                }
                return '';
            }
        ],
    ],
    [
        ['attribute'=>'is_admin','label'=>'管理员','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_admin&val=1'; # 点击开启
                $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&field=is_admin&val=0'; # 点击关闭
                if($model->is_admin == 1){
                    $txt = "<font color='green'>√</font>" ;
                    return Html::a($txt, $url1, ['title' => '取消管理员']);
                }
                if(!$model->is_admin){
                    $txt = "<font color='red'>X</font>";
                    return Html::a($txt, $url0, ['title' => '设为管理员']);
                }
                return '';
            }
        ],
        //'status',
        //'all_bet_money',
        ['attribute'=>'all_bet_money','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                return $model->all_bet_money;
            }
        ],
        ['attribute'=>'today_profits_loss','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                return $model->today_profits_loss;
            }
        ],
        ['attribute'=>'all_profits_loss','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                return $model->all_profits_loss;
            }
        ],
        ['attribute' => 'remark','label'=>'备注',//'headerOptions'=>['width'=>'20%'], //'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value' => function($model) {
                return $model->remark;
            }
        ],
        [
            'class' => 'yii\grid\ActionColumn', 'headerOptions'=>['width'=>'25%'],
            'template'=>'{act-up-balance} {act-down-balance} {act-user-edit} {act-user-balance-flows} {act-user-copy-url} {act-user-del}',
            'buttons' => [
                // 下面代码来自于 yii\grid\ActionColumn 简单修改了下
                'act-up-balance' => function ($url, $model, $key) {
                    $options = [
                        'type'=>'button',
                        'class'=>'btn-xs min-btn btn-info act-up-balance btn btn-default',
                        'data-id' => $model->id,
                        'data-name' => $model->nickName,
                    ];
                    return Html::button('上', $options);
                },
                'act-down-balance' => function ($url, $model, $key) {
                    $options = [
                        'type'=>'button',
                        'class'=>'btn-xs min-btn btn-info act-down-balance btn btn-default',
                        'data-id'=>$model->id,
                        'data-name' => $model->nickName,
                    ];
                    return Html::button('下', $options);
                },
                'act-user-balance-flows' => function ($url, $model, $key) {
                    $options = [
                        'type'=>'button',
                        'class'=>'btn-xs min-btn btn-info act-user-balance-flows btn btn-default',
                        'data-id'=>$model->id,
                        'data-name' => $model->nickName,
                    ];
                    return Html::button('查', $options);
                }
                //'act-user-del' => function ($url, $model, $key) {
                //    $options = [
                //        'type'=>'button',
                //        'class'=>'btn-xs min-btn btn-info act-user-del btn btn-default',
                //        'data-id'=>$model->id,
                //        'data-name' => $model->name,
                //    ];
                //    return Html::button('删', $options);
                //},
                //'act-user-copy-url' => function ($url, $model, $key) {
                //    $options = [
                //        'type'=>'button',
                //        'class'=>'btn-xs min-btn btn-info act-user-copy-url btn btn-default',
                //        'data-id'=>$model->id,
                //        'data-token'=>$model->token,
                //        'data-name' => $model->name,
                //    ];
                //    return Html::button('地址', $options);
                //},
                //'act-user-edit' => function ($url, $model, $key) {
                //    $options = [
                //        'type'=>'button',
                //        'class'=>'btn-xs min-btn btn-info act-user-edit btn btn-default',
                //        'data-id'=>$model->id,
                //        'data-token'=>$model->token,
                //        'data-name' => $model->name,
                //    ];
                //    return Html::button('改', $options);
                //},
            ],
        ],
        #'expire_time:datetime',
        //'created_at',
        //'updated_at',
        ['attribute' => 'update_at','label'=>'时间',//'headerOptions'=>['width'=>'20%'], //'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value' => function($model) {
                return substr($model->update_at, 5, 11);
            }
        ],
        //['class' => 'yii\grid\ActionColumn'],
    ]
);
?>
<section class="wechat-user-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <strong><?= Html::encode($this->title) ?></strong><span>（状态<strong><font color="green">√</font></strong> 为接收消息）</span>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Wechat User', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>
                -->

                <?php echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= Html::button("批量关闭 '状态'", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchCloseStatus']) ?> &nbsp;
                <?= Html::button("批量开启 '状态'", ['class' => 'btn btn-success btn-xs', 'id' => 'batchOpenStatus']) ?> &nbsp;
                <?php if($lottery_type !=\common\helpers\LotteryType::AZ_LUCKY_5){?>
                    <?= Html::button('同步好友', ['class' => 'btn btn-warning btn-xs', 'id' => 'syncFriends']) ?> &nbsp;
                    <?= Html::button("批量关闭 '需确认'", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchCloseConfirm']) ?>
                    <?= Html::button("批量开启 '需确认'", ['class' => 'btn btn-success btn-xs', 'id' => 'batchOpenConfirm']) ?>
                <?php }?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => $columns,
                    'pager' => [
                        'firstPageLabel' => '首页',  // 您可以根据需要自定义文本
                        'lastPageLabel' => '尾页',  // 您可以根据需要自定义文本
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>

<input type="hidden" name="act" id="act" value="">
<input type="hidden" name="copyTxt" id="copyTxt" value="">

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
                <h4 class="modal-title" id="tip_msg_title">提示信息</h4>
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

<!--修改积分-->
<div class="modal fade" id="tipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <form class="form-inline">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" id="tip_msg_title_up"></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group up-reason">
                        <label id="tip_msg" for="updateData"></label>
                        <input type="text" class="form-control media-middle" size="8" id="updateData" placeholder="">
                        <span id="current_tip"></span>
                    </div>
                    <div class="form-group g_token">
                        <label id="tip_edit_msg" for="account_token">token:</label>
                        <input type="text" class="form-control media-middle input-mini" size="36" name="account_token" id="account_token" placeholder="token">
                        <input type="button" id="re_token" class="btn btn-success" value="刷新token">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal" id="opConfirm">确定</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="/statics/datetimepicker/jquery.js"></script>
<script src="/chat_statics/js/clipboard.min.js"></script>
<script>
$(function () {
    function updateData(id, act, udata='') {
        var data = {id:id, act:act};
        if(act == 'act-up-balance' || act == 'act-down-balance'){
            data.balance = udata;
        }else if(act == 'act-user-edit'){
            data.name = udata;
            data.token = $("#account_token").val();
        }
        var tip_title = '';
        $.post("/agent/agent-users/up-user-data",data,function(rst) {
            if(rst.status == 200) {
                tip_title = '操作成功';
                if(act == 'act-up-balance' || act == 'act-down-balance') {
                    balance = rst.data.balance_now;
                    // 修改页面积分
                    if(rst.data.status == 200)
                        $("#balance_" + id).html(balance.toFixed(2));
                    console.log(rst.data.msg);
                }else if(act == 'act-user-edit'){
                    $("#name_" + id).html(rst.data.name_now);
                }else if(act == 'act-user-del'){
                    // 删除行
                    var ele = $("#record_"+id).parent("tr");
                    ele.remove();
                }
                $("#tip_msg_rst").html(rst.data.msg);
                $("#rstTipModal").modal('show');
            } else {
                tip_title = '操作失败';
            }
            //showTips(null, rst.msg, tip_title); # 同步完无需弹框，暂且注释
        },'JSON');
    }

    // 积分加
    $('.act-up-balance').click(function () {
        $('.g_token').hide();
        var up_id = $(this).attr('data-id');
        var up_name = $(this).attr('data-name');
        $("#act").val('act-up-balance')
        $("#updateData").val('');
        showTips(up_id, '加分：', '正在变更['+up_name+']数据：');
    });

    // 积分减
    $('.act-down-balance').click(function () {
        $('.g_token').hide();
        var up_id = $(this).attr('data-id');
        var up_name = $(this).attr('data-name');
        $("#updateData").val('');
        $("#act").val('act-down-balance');
        showTips(up_id, '扣分：', '正在变更['+up_name+']数据：');
    });

    // 编辑
    $('.act-user-edit').click(function () {
        $('.g_token').show();
        var up_id = $(this).attr('data-id');
        var up_name = $(this).attr('data-name');
        var user_token = $(this).attr('data-token');

        $("#act").val('act-user-edit');
        $("#updateData").val(up_name);
        $("#account_token").val(user_token);

        showTips(up_id, '账号:', '正在变更['+up_name+']数据，')
    });

    $('.act-user-copy-url').click(function () {
        var up_name = $(this).attr('data-name');
        var token = $(this).attr('data-token');
        $("#copy_tip_msg_title").html("用户[<strong>"+up_name+"</strong>]游戏地址");
        $("#copy_tip_msg").html('http://18.163.69.56:8090/chat/index/index?token='+ token );
        $("#act").val('act-user-copy-url');
        $("#COPY_TipModal").modal('show');
    });
    var clipboard;
    $("#CopyConfirm").click(function () {

        if(clipboard){
            clipboard.destroy();
        }

        var copyBtn = new ClipboardJS('#CopyConfirm');

        var flag = 0;
        var txt = '';
        copyBtn.on("success",function(e){
            // 复制成功
            txt = e.text;
            //alert(e.text);
            $("#copyTxt").val(e.text);
            e.clearSelection();
        });

        $("#tip_msg_title").html('复制结果');
        //$("#tip_msg_rst").html($("#copyTxt").val());
        $("#tip_msg_rst").html("复制成功");
        $("#rstTipModal").modal('show');
    });

    // 删除用户
    $('.act-user-del').click(function () {
        $("#delConfirm").attr('data-id', $(this).attr('data-id'));
        console.log($(this).attr('data-id'));
        var up_name = $(this).attr('data-name');
        $("#del_tip_msg_title").html("删除用户");
        $("#del_tip_msg").html("确定删除用户："+up_name+ '？');
        $("#act").val('act-user-del')
        $("#MSG_TipModal").modal('show');
    });

    function showTips(id, tip_msg = '积分变动', title = '提示信息') {
        console.log(id)
        //$("#updateData").val("");

        $('#tip_msg_title_up').html(title + ''+ "当前积分：" + $("#balance_"+id).html());
        $('#tip_msg').html(tip_msg);
        //$('#current_tip').html();
        $("#opConfirm").attr('op-id', id);
        $('#tipModal').modal('show');
    }

    $("#delConfirm").click(function () {
        var id = $(this).attr('data-id');
        act = $("#act").val();
        if(id != null) updateData(id, act)
    });

    $('#opConfirm').click(function () {
        var id = $(this).attr('op-id');
        balance = $("#updateData").val()
        act = $("#act").val();
        if(id != null) updateData(id, act, balance)
    });

    $("#opRstConfirm").click(function () {
        act = $("#act");
        if(act = 'act-user-edit'){
            location.reload();
        }
    });

    $("#re_token").click(function() {
        $.post("/agent/agent-users/get-token",[],function(rst) {
            console.log(rst);
            if(rst.status == 200){
                $("#account_token").val(rst.token);
            }
        });
    });

    $(".act-user-balance-flows").click(function () {
        account = $(this).attr('data-name')
        console.log(account);

        window.location.href = '/agent/agent-users-balance-flows/index?AgentUsersBalanceFlows[member_account]='+account;

    });

    $(document).on('click', '[id="syncFriends"]', function () {
        console.log('dddd');
        syncFriends()
    });

    function syncFriends() {
        $.post("/wechat/wechat-user/sync-friends",{},function(rst) {
            console.log(rst);
            if(rst.status === 200) {
                layer.msg('请求成功，稍候刷新列表', {icon: 1});
            }else {
                layer.msg(rst.msg, {icon: 7});
            }
        },'JSON');
    }

    // Batch update status
    $('#batchOpenStatus').click(function () {
        var selectedIds = $('input[name="selection[]"]:checked').map(function () {
            return this.value;
        }).get();

        batchUpdate('status', selectedIds, 1);
    });

    // Batch close status
    $('#batchCloseStatus').click(function () {
        var selectedIds = $('input[name="selection[]"]:checked').map(function () {
            return this.value;
        }).get();

        batchUpdate('status', selectedIds, 0);
    });

    // Batch update confirmation requirement
    $('#batchOpenConfirm').click(function () {
        var selectedIds = $('input[name="selection[]"]:checked').map(function () {
            return this.value;
        }).get();

        batchUpdate('is_need_confirm', selectedIds, 1);
    });

    // Batch update confirmation requirement
    $('#batchCloseConfirm').click(function () {
        var selectedIds = $('input[name="selection[]"]:checked').map(function () {
            return this.value;
        }).get();

        batchUpdate('is_need_confirm', selectedIds, 0);
    });

    function batchUpdate(field, ids, val) {
        console.log(field, ids, val)
        if (ids.length <= 0) {
            layer.alert('至少选择一项')
        }
        // Perform AJAX request to update the selected items
        $.post('/wechat/wechat-user/batch-switch-status', { field: field, ids: ids , val: val}, function (response) {
            if (response.status === 200) {
                layer.alert('更新成功', function(index){
                    layer.close(index); // Close the alert
                    setTimeout(function(){
                        location.reload(); // Reload the current page after 2 seconds
                    }, 1000); // 2000 milliseconds (2 seconds)
                });
            } else {
                layer.alert('Batch update failed.');
            }
        }, 'json');
    }
});
</script>
