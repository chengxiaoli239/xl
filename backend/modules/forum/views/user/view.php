<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\BaseStringHelper;

/* @var $this yii\web\View */
/* @var $model backend\models\User */

$this->title = Yii::t('app', 'My Infomation');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$balance = \backend\models\TzSystemsUsers::findOne(['uid'=>1, 'tz_system_id'=>2])->balance;
?>
<section class="user-view wrapper site-min-height">
    <div class="row">
        <div class="col-lg-12">
            <?php foreach ($models as $model){?>
            <section class="panel">
                <header class="panel-heading">
                    <?= Html::encode(\backend\models\TzSystems::findOne($model->tz_system_id)->name) ?>
                    <!--?= Html::a(Yii::t('app', '同步余额'), ['syn-balance']) ?-->
                    <span style="margin-right: 10px"><?= Html::a(Yii::t('app', 'edit'), "/forum/tz-systems-users/update?id=".$model->id, [ 'class' => 'btn btn-primary', 'id'=>$model->id ]) ?></span>
                    <span style="margin-right: 10px"><?= Html::a(Yii::t('app', 'Update Balance'), '#', [ 'class' => 'btn btn-primary update-balance', 'id'=>$model->id ]) ?></span>
                </header>
                <div class="panel-body">
                    <!--p>
                        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger',
                            'data' => [
                                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]) ?>
                    </p-->
                    <div class="row">
                        <div class="col-lg-11">
                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => [
                                    //'id',
                                    //'tz_system_id',
                                    'sys_name',
                                    'account',
                                    //'balance',
                                    [ 'attribute'=>'balance','label'=>'系统余额','format'=>'raw',
                                        'value'=>function($model){
                                            return "<span id='balance_".$model->id."'>".$model->balance."</span>";
                                        }
                                    ],
                                    //'email:email',
                                    'password',
                                    //'expire_time:datetime',
                                    ['attribute'=>'expire_time',//'label'=>'状态',
                                        'format'=>'raw',
                                        'value'=>function($model){
                                            $txt = \backend\service\UserService::accountIsExpireDesc($model->uid, $model->tz_system_id);
                                            return $txt;
                                        }
                                    ],
                                    //'ssc_domain',
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
                                    ['attribute' => 'flow_wp_accounts', 'label'=>'网盘跟买', 'headerOptions' => ['width' => '8%'],
                                        'format'=>'raw',
                                        'value'=> function($model){
                                            $set = Html::a('设置', 'javascript:;', ['id' => 'setWpFollow','alt'=>'设置跟买', 'class'=>'btn btn-xs']);
                                            $txt = $model->flow_wp_accounts ? '<strong><font color="green">正买</font></strong>：'.implode('、', explode(',', $model->flow_wp_accounts)).'['.$model->flow_wp_player_bs.'倍]' : '';
                                            $txt .= $model->flow_op_accounts ? ' &nbsp;<strong><font color="red">反买</font></strong>：'.implode('、', explode(',', $model->flow_op_accounts)).'['.$model->flow_op_player_bs.'倍]' : '';

                                            $follow_txt = $model->follow_status ? '<font color="green">已开启</font>' : '<font color="red">已关闭</font>';
                                            $url = '/forum/user/switch-field-status?id='.$model->id.'&field=follow_status&status='.($model->follow_status?0:1);
                                            $follow_a = Html::a($follow_txt, $url, ['title' => '点击切换','alt'=>'点击切换']);

                                            return $txt.' &nbsp; '.$set. '&nbsp; 开关：'.$follow_a;
                                        },
                                    ],
                                    ['attribute' => 'cookie', 'label'=>'cookie',
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            $txt = BaseStringHelper::truncate($model->cookie,24);
                                            return Html::a($txt, 'javascript:;', ['title' => $model->cookie,'alt'=>$model->cookie]);
                                        }
                                    ],
                                    [ 'attribute'=>'access_token','label'=>'token','value'=>function($model){
                                        return $model->access_token;
                                    }],
                                    ['attribute' => 'odds_2d', 'label'=>'赔率', //'headerOptions' => ['width' => '170'],
                                        'value'=> function($model){
                                            return  '二定:'.$model->odds_2d .'、 '.'三定:'.$model->odds_3d .'、 '.'四定:'.$model->odds_4d;
                                        },
                                    ],
                                    ['attribute' => 'odds_3d', 'label'=>'盈利x', //'headerOptions' => ['width' => '170'],
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            $set = Html::a('设置', 'javascript:;', ['id' => 'setProfits','alt'=>'设置止盈止损', 'class'=>'btn btn-xs']);
                                            $resetProfits = Html::a('归零', 'javascript:;', ['id' => 'resetProfits','alt'=>'止盈止损归零', 'class'=>'btn btn-xs']);
                                            return  '止盈:'.$model->take_profits.'  止损:'.$model->stop_loss
                                                .' 当前:<font color="'.($model->current_profits<0?'red':'green').'">'.$model->current_profits.'</font>  '.$set.' '.$resetProfits;
                                        }
                                    ],
                                    [ 'attribute'=>'update_time','label'=>'更新时间','value'=>function($model){
                                        return $model->update_time;
                                    }],
                                    ['attribute' => 'wechat_service', 'label'=>'客服微信',
                                        'format'=>'raw',
                                        'value'=> function($model){
                                            $wechatId = 'TedGod';
                                            $viewBtn = Html::a('查看', 'javascript:;', [
                                                'id' => 'viewWechatQr',
                                                'alt' => '查看微信二维码',
                                                'class' => 'btn btn-xs btn-info'
                                            ]);
                                            return $wechatId . ' &nbsp; ' . $viewBtn;
                                        }
                                    ],
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php }?>
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

<!--微信二维码-->
<div class="modal fade" id="wechatQrModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title">客服微信二维码</h4>
            </div>
            <div class="modal-body text-center">
                <div class="form-group">
                    <img src="/statics/img/service.jpg" alt="微信二维码" class="img-responsive" style="max-width: 200px; margin: 0 auto;" id="wechatQrImage">
                </div>
                <p class="text-muted">微信号：TedGod</p>
                <p class="text-muted small">长按二维码保存到相册</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" id="saveQrCode">保存二维码</button>
            </div>
        </div>
    </div>
</div>

<script src="/statics/js/jquery-2.0.3.js"></script>
<!--script src="https://cdn.bootcss.com/jquery/2.0.3/jquery.js"></script-->
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

    // 微信二维码查看
    $('#viewWechatQr').click(function () {
        $('#wechatQrModal').modal('show');
    });

    // 保存二维码功能
    $('#saveQrCode').click(function () {
        var img = document.getElementById('wechatQrImage');
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        
        ctx.drawImage(img, 0, 0);
        
        canvas.toBlob(function(blob) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'wechat_qr_tedgod.jpg';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 'image/jpeg', 0.9);
    });

    // 长按保存功能（移动端）
    $('#wechatQrImage').on('contextmenu', function(e) {
        e.preventDefault();
        // 在移动端，长按会触发contextmenu事件
        // 这里可以添加提示信息
        alert('请长按图片选择保存到相册');
    });

});
</script>
