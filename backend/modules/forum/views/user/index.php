<?php

use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
$user = \Yii::$app->user;
$columns = array_merge(
        $user->id!=1?[]:
        [
            ['class' => 'yii\grid\SerialColumn'],
        ],
        [
            ['attribute' => 'username','label'=>'账号', # 'headerOptions'=>['width'=>'5%'],
                'value' => function($model){
                    return $model->username;
                }
            ],
            ['attribute'=>'desc','label'=>'备注',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                'format'=>'raw',
                'value'=>function($model){
                    $TzSystemsUsers = \backend\models\TzSystemsUsers::findOne(['uid'=>$model->id]);
                    return $TzSystemsUsers->desc.' 余额：'.floatval($TzSystemsUsers->balance);
                }
            ],
            ['attribute' => 'status','label'=>'状态', # 'headerOptions'=>['width'=>'5%'],
                'format'=>'raw',
                'value' => function($model) {
                    if($model->status == 1){
                        $txt = '<font color="red">已禁用</font>';
                        $alt = '点击启用';
                        $val = 10;
                    }else{
                        $txt = '<font color="green">已启用</font>';
                        $val = 1;
                        $alt = '点击禁用';
                    }
                    $url = "/forum/user/switch-status?id=".$model->id."&status=".$val; #
                    return Html::a($txt, $url, ['title' => '开通系统权限','alt'=>$alt]);
                }
            ],
            ['attribute' => 'is_can_op_bet','label'=>'反向下', # 'headerOptions'=>['width'=>'5%'],
                'format'=>'raw',
                'value' => function($model) {
                    if($model->is_can_op_bet == 1){
                        $txt = '<font color="red">已关闭</font>';
                        $alt = '点击开启';
                        $val = 2;
                    }else{
                        $txt = '<font color="green">已开启</font>';
                        $val = 1;
                        $alt = '点击关闭';
                    }
                    $url = "/forum/user/switch-status?id=".$model->id.'&field=is_can_op_bet'."&status=".$val; #
                    return Html::a($txt, $url, ['title' => '开通反向','alt'=>$alt]);
                }
            ],
        ],
        $user->id!=1?[
            [
                'attribute' => 'desc', 'label'=>'信息',
            ]
        ]:
        [
            ['attribute' => 'id','label'=>'更新时间', # 'headerOptions'=>['width'=>'5%'],
                'value' => function($model){
                    return date('Y-m-d H:i:s', $model->updated_at);
                }
            ],
            ['attribute' => 'id','label'=>'投注系统权限', # 'headerOptions'=>['width'=>'5%'],
                'format'=>'raw',
                'value' => function($model) {
                    $url = "/forum/user/open-systems?uid=".$model->id; #
                    return Html::a('添加/编辑', $url, ['title' => '开通系统权限','alt'=>$model->id]);
                }
            ],
            //'email',
            ['attribute'=>'desc',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                'format'=>'raw',
                'value'=>function($model){
                    $TzSystemsUsers = \backend\models\TzSystemsUsers::findOne(['uid'=>$model->id]);
                    $options = [
                        'class'=>'act-user-copy',
                        'data-id'=>$model->id,
                        'data-username'=>$model->username,
                        'data-desc'=>$model->desc,
                        'data-access_token'=>$TzSystemsUsers->access_token??'',
                    ];
                    return Html::a($model->desc, 'javascript:;', $options);
                }
            ],
        ],
        [
            [
                'label'=>'操作',
                'format'=>'raw',
                'value'=>function($model){
                    $user = \Yii::$app->user;
                    $tz = \backend\models\TzSystemsUsers::findOne(['uid'=>$model->id]);
                    $accInfo = $tz ? "account:'{$tz->account}' pw_len:".strlen($tz->password?:'') : '';
                    return Html::a(Yii::t('app', 'edit'),  'javascript:void(0);', [
                            'class'=>'btn btn-xs btn-success edit-btn',
                            'style'=>'margin-bottom:15px;',
                            'data-url' => Yii::$app->urlManager->createUrl(['forum/user/create-user', 'id' => $model->id]),
                        ]).' '
                        .Html::a('账号编辑', 'javascript:void(0);', [
                            'class'=>'btn btn-xs btn-info btn-edit-account',
                            'style'=>'margin-bottom:15px;',
                            'data-id'=>$tz->id??0,
                            'data-account'=>$tz->account??'',
                            'data-domain'=>$tz->ssc_domain??'',
                            'data-title'=>($model->username).' - '.$accInfo,
                        ]).' '
                        .Html::a('删除', ['delete', 'id'=>$model->id], ['class'=>'btn btn-xs btn-warning', 'style'=>'margin-bottom:15px;']);
                }
            ]
        ]
);
$js=<<<'JS'
    $('.edit-btn').click(function(){
        var url = $(this).attr('data-url');
        console.log(url);
        $('#create-user-modal .modal-content').load(url, function() {
            $('#create-user-modal').modal('show');
        });
    });
JS;
$this->registerJs($js);

?>
<section class="user-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create User'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

            <?php //Pjax::begin(); ?>
                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => $columns
                ]); ?>
            <?php //Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>

<!--复制提示框-->
<div class="modal fade" id="COPY_TipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="copy_tip_msg_title"></h4>
            </div>
            <div class="modal-body">
                <div class="form-group up-reason">
                    <input type="hidden" id="tz_user_id" value="0">
                    <label id="copy_tip_msg" for="copy_tip_msg"></label><span></span>
                    <label id="copy_access_token" for="copy_access_token"></label>
                    <span>&nbsp;&nbsp; <a class="btn btn-xs" id="reset_token" href="javascript:;">重置token</a></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-clipboard-target="#copy_tip_msg" id="CopyConfirm">复制</button>
            </div>
        </div>
    </div>
</div>

<!--盘口账号编辑弹窗-->
<div class="modal fade" id="EditAccountModal" tabindex="-1" role="dialog"
     style="display:none;left:50%;top:50%;transform:translate(-50%,-50%);min-width:50%;">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title" id="edit_account_title">编辑盘口账号</h4>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_account_id">
            <div class="form-group">
                <label>盘口账号</label>
                <input class="form-control" id="edit_account" placeholder="盘口登录账号">
            </div>
            <div class="form-group">
                <label>盘口密码</label>
                <input class="form-control" id="edit_password" placeholder="留空不修改，填'clear'清空">
            </div>
            <div class="form-group">
                <label>网盘地址</label>
                <input class="form-control" id="edit_domain" placeholder="https://f1.xxx.xyz">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
            <button type="button" class="btn btn-primary" id="save_account_btn">保存</button>
        </div>
    </div></div>
</div>

<script src="/statics/js/jquery-2.0.3.js"></script>
<!--script src="https://cdn.bootcss.com/jquery/2.0.3/jquery.js"></script-->
<script src="/chat_statics/js/clipboard.min.js"></script>
<script>
$(function () {
    // 盘口账号编辑
    $('.btn-edit-account').click(function(){
        var id = $(this).data('id');
        var account = $(this).data('account');
        var domain = $(this).data('domain');
        var title = $(this).data('title');
        $('#edit_account_title').text('编辑盘口账号 - ' + title);
        $('#edit_account_id').val(id);
        $('#edit_account').val(account);
        $('#edit_password').val('');
        $('#edit_domain').val(domain);
        $('#EditAccountModal').modal('show');
    });

    $('#save_account_btn').click(function(){
        var id = $('#edit_account_id').val();
        var account = $('#edit_account').val();
        var password = $('#edit_password').val();
        var domain = $('#edit_domain').val();
        var data = {id: id, account: account, ssc_domain: domain};
        if(password !== ''){
            data.password = (password === 'clear') ? '' : password;
        }
        $.post('/forum/tz-systems-users/update-account', data, function(rst){
            if(rst.status == 200){
                alert('保存成功');
                location.reload();
            }else{
                alert(rst.msg || '保存失败');
            }
        }, 'json').fail(function(){ alert('请求失败'); });
    });

    $('.act-user-copy').click(function () {
        var desc = $(this).data('desc');
        var username = $(this).data('username');
        var access_token = $(this).data('access_token');
        $("#copy_tip_msg_title").html("用户[<strong>" + username + "</strong>]");
        $("#copy_tip_msg").html('http://' + window.location.host + '\r\n' + desc);
        $("#tz_user_id").val($(this).data('id'))
        $("#copy_access_token").html("access_token：[<strong>" + access_token + "</strong>]");
        $("#act").val('act-user-copy');
        $("#COPY_TipModal").modal('show');
    });

    var clipboard;
    $("#CopyConfirm").click(function () {
        if (clipboard) {
            clipboard.destroy();
        }

        var copyBtn = new ClipboardJS('#CopyConfirm');

        var flag = 0;
        var txt = '';
        copyBtn.on("success", function (e) {
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

    $('#reset_token').click(function(){
        user_id = $('#tz_user_id').val();
        data = {'user_id':user_id}
        $.post("/forum/user/reset-token",data,function(rst) {
            if(rst.status == 200) {
                Ewin.confirm({ message: '新access_token：'+rst.data.access_token}).on(function (e) {});
            } else {
                Ewin.confirm({ message: '操作失败：'+rst.msg}).on(function (e) {});
            }
            //showTips(null, rst.msg, tip_title); // 同步完无需弹框，暂且注释
        },'JSON');
    });
});
</script>


<!-- 模态框 -->
<?php Modal::begin([
    'id' => 'create-user-modal',
    'size' => 'modal-lg',
]); ?>
<?php Modal::end(); ?>

