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
                                    [ 'attribute'=>'tz_system_id','label'=>'系统时间','value'=>function($model){
                                        return $model->update_time;
                                    }],
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
                                            /*
                                            $date_time = 86400; # 一天时间戳
                                            if(!empty($model->expire_time)){
                                                if($model->expire_time <= time()){
                                                    $txt = '<font color="red">已过期，请续费</font>';
                                                }elseif($model->expire_time - 2 * $date_time < time()){
                                                    $txt = date('m-d H:i', $model->expire_time) . ' [<font color="red">即将到期</font>]';
                                                }else{
                                                    $txt = date('m-d H:i', $model->expire_time);
                                                }
                                            }else{
                                                $txt = '<font color="green">永久生效</font>';
                                            }
                                            */
                                            $txt = \backend\service\UserService::accountIsExpireDesc($model->uid, $model->tz_system_id);

                                            return $txt;
                                        }
                                    ],
                                    'ssc_domain',
                                    [ 'attribute'=>'status','label'=>'状态',
                                        'format'=>'raw',
                                        'value'=>function($model){
                                            return $model->status ? '<font color="green">已激活</font>' : '<font color="red">已禁用</font>';
                                        }
                                    ],
                                    'desc',
                                    ['attribute' => 'cookie', 'label'=>'cookie',
                                        'format'=>'raw',
                                        'value' => function($model) {
                                            $txt = BaseStringHelper::truncate($model->cookie,24);
                                            return Html::a($txt, '#', ['title' => $model->cookie,'alt'=>$model->cookie]);
                                        }
                                    ],
                                    [ 'attribute'=>'update_time','label'=>'更新时间','value'=>function($model){
                                        return $model->update_time;
                                    }],
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

<script src="https://cdn.bootcss.com/jquery/2.0.3/jquery.js"></script>
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
});
</script>
