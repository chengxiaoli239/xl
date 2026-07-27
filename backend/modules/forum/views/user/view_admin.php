<?php

use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use common\widgets\Alert;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\TzSystemsUsers */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Tz Systems Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="tz-systems-users-index wrapper site-min-height">
    <!-- page start-->
    <?= Alert::widget() ?>
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Tz Systems Users'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/view_tab.php'); ?>
                <?php //echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'uid',
                        ['attribute' => 'uid', 'label'=>'uid', 'headerOptions' => ['width' => '5%'],
                            'value'=> function($model){
                                return $model->uid ? $model->uid : '';
                            },
                        ],
                        //'tz_system_id',
                        //'username',
                        ['attribute' => 'username', 'label'=>'账号', 'headerOptions' => ['width' => '8%'],
                            'format'=>'raw',
                            'value'=> function($model){
                                $txt = $model->username ? $model->username : '';
                                $options = [
                                    'class' => 'act-login',
                                    'data-id'=>$model->id,
                                    'data-uid'=>$model->uid,
                                    'data-username'=>$model->username, # 系统账号
                                    'data-account'=>$model->account, # 网盘账号
                                    'data-domain'=>$model->ssc_domain, # 网盘地址
                                    'data-ssl-mode'=>TzSystemsUsers::SSL_MODE_OPTIONS[(int)$model->ssl_mode] ?? '继承全局',
                                ];
                                return Html::a($txt, 'javascript:;', $options);
                            },
                        ],
                        //'sys_name',
                        ['attribute' => 'sys_name', 'label'=>'系统', 'headerOptions' => ['width' => '8%'],
                            'value'=> function($model){
                                return $model->sys_name ? $model->sys_name : '';
                            },
                        ],
                        //'account',
                        ['attribute' => 'account', 'label'=>'账号', 'headerOptions' => ['width' => '8%'],
                            'value'=> function($model){
                                return $model->account ? $model->account : '';
                            },
                        ],
                        //'password',
                        ['attribute' => 'password', 'label'=>'密码', 'headerOptions' => ['width' => '5%'],
                            'value'=> function($model){
                                return $model->password ? $model->password : '';
                            },
                        ],
                        //'balance',
                        ['attribute' => 'balance', 'label'=>'余额', 'headerOptions' => ['width' => '8%'],
                            'format'=>'raw',
                            'value'=> function($model){
                                $options = [
                                    'id' => 'balance_'.$model->id
                                ];
                                $txt = Html::a($model->balance?:'0.00', '#', $options);
                                if($model->current_profits>=0){
                                    $rst = $txt.'[<font color="green">'.$model->current_profits.'</font>]';
                                }else{
                                    $rst = $txt.'[<font color="red">'.$model->current_profits.'</font>]';
                                }
                                return $rst;
                                //return $model->balance ? $model->balance : '';
                            },
                        ],
                        //'status',
                        /*
                        ['attribute' => 'status', 'label'=>'状态', //'headerOptions' => ['width' => '170'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return $model->status ? '<font color="green">已启用</font>' : '<font color="red">已禁用</font>';
                            },
                        ],
                        */
                        ['attribute' => 'status','label'=>'状态', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if($model->status == 1){
                                    $txt = '<font color="green">已启用</font>';
                                    $alt = '点击禁用';
                                    $val = 0;
                                }else{
                                    $txt = '<font color="red">已禁用</font>';
                                    $val = 1;
                                    $alt = '点击启用';
                                }
                                $url = "/forum/user/switch-tz-system-status?id=".$model->id."&status=".$val; #
                                return Html::a($txt, $url, ['title' => '开通系统权限','alt'=>$alt]);
                            }
                        ],
                        ['attribute' => 'is_use_proxy','label'=>'需代理', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if($model->is_use_proxy == 1){
                                    $txt = '<font color="green">是</font>';
                                    $alt = '点击禁用';
                                    $val = 0;
                                }else{
                                    $txt = '<font color="red">否</font>';
                                    $val = 1;
                                    $alt = '点击启用';
                                }
                                $url = "/forum/user/switch-proxy?id=".$model->id."&status=".$val; #
                                return Html::a($txt, $url, ['title' => '开通使用代理IP','alt'=>$alt]);
                            }
                        ],
                        ['attribute' => 'is_proxy_login','label'=>'登录代理', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if((int)$model->is_proxy_login === 1){
                                    $txt = '<font color="green">是</font>';
                                    $alt = '点击关闭登录代理';
                                }else{
                                    $txt = '<font color="red">否</font>';
                                    $alt = '点击开启登录代理';
                                }
                                $url = "/forum/user/switch-proxy-scene?id=".$model->id."&field=is_proxy_login";
                                return Html::a($txt, $url, ['title' => '登录接口是否走代理','alt'=>$alt]);
                            }
                        ],
                        ['attribute' => 'is_proxy_bet','label'=>'接口代理', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if((int)$model->is_proxy_bet === 1){
                                    $txt = '<font color="green">是</font>';
                                    $alt = '点击关闭非登录代理';
                                }else{
                                    $txt = '<font color="red">否</font>';
                                    $alt = '点击开启非登录代理';
                                }
                                $url = "/forum/user/switch-proxy-scene?id=".$model->id."&field=is_proxy_bet";
                                return Html::a($txt, $url, ['title' => '非登录/下注接口是否走代理','alt'=>$alt]);
                            }
                        ],
                        ['attribute' => 'ssl_mode','label'=>'TLS', 'headerOptions'=>['width'=>'8%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::dropDownList(
                                    'ssl_mode_'.$model->id,
                                    (int)$model->ssl_mode,
                                    TzSystemsUsers::SSL_MODE_OPTIONS,
                                    [
                                        'class'=>'form-control input-sm ssl-mode-select',
                                        'data-id'=>$model->id,
                                        'data-saved-value'=>(int)$model->ssl_mode,
                                        'title'=>'该盘口账号的TLS连接模式',
                                    ]
                                );
                            }
                        ],
                        ['attribute' => 'is_local_bet','label'=>'下注位置', 'headerOptions'=>['width'=>'12%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $isLocal = (int)$model->is_local_bet !== BetsBackend::BET_TYPE_SERVER_API;
                                $cloud = Html::a('云服务器', [
                                    '/forum/user/switch-is-local-bet',
                                    'id'=>$model->id,
                                    'status'=>BetsBackend::BET_TYPE_SERVER_API,
                                ], [
                                    'class'=>'btn btn-xs '.(!$isLocal ? 'btn-success' : 'btn-default'),
                                    'data'=>['method'=>'post', 'confirm'=>'确定切换到云服务器下注？'],
                                ]);
                                $local = Html::a('本地电脑', [
                                    '/forum/user/switch-is-local-bet',
                                    'id'=>$model->id,
                                    'status'=>BetsBackend::BET_TYPE_LOCAL_API,
                                ], [
                                    'class'=>'btn btn-xs '.($isLocal ? 'btn-success' : 'btn-default'),
                                    'data'=>['method'=>'post', 'confirm'=>'确定切换到本地电脑下注？'],
                                ]);
                                return Html::tag('div', $cloud.$local, ['class'=>'btn-group', 'role'=>'group']);
                            }
                        ],
                        ['attribute' => 'follow_status','label'=>'自动跟', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if($model->follow_status == 1){
                                    $txt = '<font color="green">是</font>';
                                    $alt = '点击关闭';
                                    $val = 0;
                                }else{
                                    $txt = '<font color="red">否</font>';
                                    $val = 1;
                                    $alt = '点击启用';
                                }
                                $url = '/forum/user/switch-field-status?id='.$model->id.'&field=follow_status&status='.$val;
                                return Html::a($txt, $url, ['title' => '自动跟开启','alt'=>$alt]);
                            }
                        ],
                        ['attribute' => 'is_auto_login','label'=>'自动登', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if($model->is_auto_login == 1){
                                    $txt = '<font color="green">是</font>';
                                    $alt = '点击关闭';
                                    $val = 0;
                                }else{
                                    $txt = '<font color="red">否</font>';
                                    $val = 1;
                                    $alt = '点击启用';
                                }
                                $url = "/forum/user/switch-auto-login?id=".$model->id."&status=".$val; #
                                return Html::a($txt, $url, ['title' => '自动登陆开启','alt'=>$alt]);
                            }
                        ],
                        ['attribute' => 'is_auto_bet','label'=>'自动下', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if($model->is_auto_bet == 1){
                                    $txt = '<font color="green">是</font>';
                                    $alt = '点击禁用';
                                    $val = 0;
                                }else{
                                    $txt = '<font color="red">否</font>';
                                    $val = 1;
                                    $alt = '点击启用';
                                }
                                $url = "/forum/user/switch-auto-bet-status?id=".$model->id."&status=".$val; #
                                return Html::a($txt, $url, ['title' => '自动下注脚本开启','alt'=>$alt]);
                            }
                        ],
                        //'ssc_domain',
                        ['attribute' => 'ssc_domain', 'label'=>'网盘', //'headerOptions' => ['width' => '170'],
                            'value'=> function($model){
                                return  $model->ssc_domain;
                            },
                        ],
                        [ 'attribute'=>'desc','label'=>'状态',
                            'format'=>'raw',
                            'value'=>function($model){
                                return empty($model->desc) ? '<font color="green">正常</font>' : '<font color="red">'.$model->desc.'</font>';
                            }
                        ],
                        ['attribute' => 'flow_wp_accounts', 'label'=>'跟随账号', 'headerOptions' => ['width' => '8%'],
                            'value'=> function($model){
                                $txt = $model->flow_wp_accounts ? '['.$model->flow_wp_player_bs.'倍]正:'.$model->flow_wp_accounts : '';
                                $txt .= $model->flow_op_accounts ? '['.$model->flow_op_player_bs.'倍]反:'.$model->flow_op_accounts : '';
                                return $txt;
                            },
                        ],
                        //'expire_time:datetime',
                        ['attribute' => 'expire_time', 'label'=>'到期',// 'headerOptions' => ['width' => '170'],
                            'format'=>'raw',
                            'value'=> function($model){
                                $txt = \backend\service\UserService::accountIsExpireDesc($model->uid, $model->tz_system_id);
                                return $txt;
                            },
                        ],
                        //'cookie',
                        //'created_at:datetime',
                        ['attribute' => 'update_time', 'label'=>'更新时间',// 'headerOptions' => ['width' => '170'],
                            'value'=> function($model){
                                return  substr($model->update_time, 5, -3);   //主要通过此种方式实现
                            },
                        ],
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<script src="/statics/js/jquery-2.0.3.js"></script>
<?php include(dirname(__FILE__).'/user-renew.php'); ?>
<?php include(dirname(__FILE__).'/act-user-login.php'); ?>

<?php
$this->registerJs(<<<'JS'
$(document).on('change', '.ssl-mode-select', function () {
    var select = $(this);
    var oldValue = select.data('saved-value');
    select.prop('disabled', true);
    $.post('/forum/tz-systems-users/set-ssl-mode', {
        id: select.data('id'),
        ssl_mode: select.val()
    }, function (rst) {
        if (rst.status === 200) {
            select.data('saved-value', select.val());
            $('.act-login[data-id="' + select.data('id') + '"]')
                .data('ssl-mode', rst.ssl_mode_label)
                .attr('data-ssl-mode', rst.ssl_mode_label);
            return;
        }
        select.val(oldValue);
        alert(rst.msg || 'TLS模式保存失败');
    }, 'json').fail(function () {
        select.val(oldValue);
        alert('TLS模式保存失败');
    }).always(function () {
        select.prop('disabled', false);
    });
});
JS
);
?>
