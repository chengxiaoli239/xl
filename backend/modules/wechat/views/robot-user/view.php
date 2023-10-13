<?php

use izyue\admin\widgets\GridView;
use izyue\admin\widgets\ListView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\RobotUser */

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
                                    'wId',
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
                                            if(!empty($History)){
                                                $imgUrl = $History->smallHeadImgUrl;
                                            }
                                            $txt = '<img width="30" height="30" src="'.$imgUrl.'">&nbsp;&nbsp;';
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
                                    $txt = '<img width="30px" height="30px" src="'.$model['smallHeadImgUrl'].'">';
                                    return $txt;
                                }
                            ],
                            ['attribute' => 'wechat_status','label'=>'状态', 'headerOptions'=>['width'=>'20%'],
                                'format'=>'raw',
                                'value' => function($model) {
                                    return '<strong>'.($model['wechat_status']?'<font color="green">在线</font>' : '<font color="gray">离线</font>').'</strong>';
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
                                            ['class'=>'btn btn-success btn-xs', 'data-wechatId'=>$model['wcId'], 'data-status'=>0, 'id'=>'change_id_'.$model['id']]
                                        );
                                        $onlineTxt = Html::button(
                                            '点击退出<span class="glyphicon glyphicon-arrow-down"></span>',
                                            ['class'=>'btn btn-danger btn-xs', 'data-wechatId'=>$model['wcId'], 'data-status'=>1, 'id'=>'change_id_'.$model['id']]
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
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
    $(function () {
        historyLists = <?php echo \yii\helpers\Json::encode($historyRecords)?>;
        function switchWechat(wechatId, switchStatus) {
            var data = {wechatId:wechatId, switchStatus:switchStatus};
            var tip_title = '';
            $.post("/wechat/robot-user/switch-wechat",data,function(rst) {
                console.log(rst);
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
                                if(rst.status === 200) {
                                    // 返回成功刷新网页
                                    Ewin.confirm({ message: '登录成功'}).on(function (e) {
                                        location.reload();
                                    });
                                }
                            });
                        }, 5000)
                    }
                } else {
                    Ewin.alert(rst.msg);
                }
            },'JSON');
        }

        function switchWechatx(wechatId, switchStatus) {
            var data = {wechatId:wechatId, switchStatus:switchStatus};
            console.log('获取开始...')
            setTimeout(function () {
                $.post("/wechat/robot-user/act-wechat-login",{wId:wId, 'wcId':wechatId},function(loginRst) {
                    if(rst.status === 200) {
                        // 返回成功刷新网页
                        Ewin.confirm({ message: '登录成功'}).on(function (e) {
                            location.reload();
                        });
                    }
                });
            }, 5000)
        }

        $(document).on('click', '[id^="change_id_"]', function () {
            var wechatId = $(this).attr('data-wechatid');
            var nowStatus = $(this).attr('data-status');
            switchStatus = (nowStatus==1) ? 0 : 1
            console.log(wechatId, nowStatus);
            switchWechat(wechatId, switchStatus)
            //switchWechatx(wechatId, switchStatus)
        });

        $('.open_bet_status').click(function () {
            var plan_id = $(this).attr('plan_id');
            showTips(plan_id);
        });

        $("#changeList").click(function (rst) {
            //$('#rst_code').text(JSON.stringify(push_desc, null,' '))
            //$('#push_content').text(JSON.stringify(push_content,null,' '))

            $('#exampleModal_msg').modal('show');
        });
        // exampleModal_QrCode
        function syncLoginStatus() {

        }
    })
</script>




























