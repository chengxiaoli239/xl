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
$textValue = static function ($value) {
    $value = trim((string)$value);
    return $value === '' ? Html::tag('span', '-', ['class'=>'text-muted']) : Html::encode($value);
};
$summary = static function (array $rows) {
    $content = '';
    foreach ($rows as $row) {
        $content .= Html::tag('div',
            Html::tag('span', Html::encode($row[0]), ['class'=>'admin-summary-label']).
            Html::tag('span', $row[1], ['class'=>'admin-summary-value']),
            ['class'=>'admin-summary-line']
        );
    }

    return Html::tag('div', $content, ['class'=>'admin-summary']);
};
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
                        //'tz_system_id',
                        ['attribute' => 'uid_username', 'label'=>'UID/账号', 'headerOptions' => ['width' => '10%'],
                            'contentOptions'=>['class'=>'admin-summary-cell admin-login-cell'],
                            'format'=>'raw',
                            'value'=> function($model) use ($summary, $textValue){
                                $options = [
                                    'class' => 'act-login',
                                    'data-id'=>$model->id,
                                    'data-uid'=>$model->uid,
                                    'data-username'=>$model->username, # 系统账号
                                    'data-account'=>$model->account, # 网盘账号
                                    'data-domain'=>$model->ssc_domain, # 网盘地址
                                    'data-ssl-mode'=>TzSystemsUsers::SSL_MODE_OPTIONS[(int)$model->ssl_mode] ?? '继承全局',
                                ];
                                $login = Html::a($textValue($model->username), 'javascript:;', $options);
                                return $summary([
                                    ['UID', $textValue($model->uid)],
                                    ['账号', $login],
                                ]);
                            },
                        ],
                        ['attribute' => 'account_summary', 'label'=>'盘口/账号', 'format'=>'raw',
                            'contentOptions'=>['class'=>'admin-summary-cell'],
                            'value'=> function($model) use ($summary, $textValue){
                                return $summary([
                                    ['盘口', $textValue($model->sys_name)],
                                    ['账号', $textValue($model->account)],
                                    ['密码', $textValue($model->password)],
                                    ['地址', $textValue($model->ssc_domain)],
                                ]);
                            },
                        ],
                        ['attribute' => 'account_status', 'label'=>'余额/状态', 'format'=>'raw',
                            'contentOptions'=>['class'=>'admin-summary-cell'],
                            'value'=> function($model) use ($summary, $textValue){
                                $balance = Html::a(
                                    $textValue($model->balance ?: '0.00'), '#',
                                    ['id'=>'balance_'.$model->id]
                                );
                                $profitClass = $model->current_profits >= 0 ? 'text-success' : 'text-danger';
                                $balance .= ' '.Html::tag('span', '['.Html::encode($model->current_profits).']',
                                    ['class'=>$profitClass]);
                                $statusValue = $model->status == 1 ? '已启用' : '已禁用';
                                $statusClass = $model->status == 1 ? 'text-success' : 'text-danger';
                                $statusUrl = '/forum/user/switch-tz-system-status?id='.$model->id.'&status='.($model->status == 1 ? 0 : 1);
                                $status = Html::a(Html::tag('span', $statusValue, ['class'=>$statusClass]), $statusUrl,
                                    ['title'=>'开通系统权限', 'alt'=>$model->status == 1 ? '点击禁用' : '点击启用']);
                                $desc = empty($model->desc)
                                    ? Html::tag('span', '正常', ['class'=>'text-success'])
                                    : Html::tag('span', Html::encode($model->desc), ['class'=>'text-danger']);
                                return $summary([
                                    ['余额', $balance],
                                    ['状态', $status],
                                    ['说明', $desc],
                                    ['到期', \backend\service\UserService::accountIsExpireDesc($model->uid, $model->tz_system_id)],
                                ]);
                            },
                        ],
                        ['attribute' => 'proxy_summary', 'label'=>'代理', 'format'=>'raw',
                            'contentOptions'=>['class'=>'admin-summary-cell'],
                            'value'=>function($model) use ($summary){
                                $flag = static function ($enabled) {
                                    return Html::tag('span', $enabled ? '是' : '否',
                                        ['class'=>$enabled ? 'text-success' : 'text-danger']);
                                };
                                $proxyEnabled = (int)$model->is_use_proxy === 1;
                                $proxyUrl = '/forum/user/switch-proxy?id='.$model->id.'&status='.($proxyEnabled ? 0 : 1);
                                $loginUrl = '/forum/user/switch-proxy-scene?id='.$model->id.'&field=is_proxy_login';
                                $betUrl = '/forum/user/switch-proxy-scene?id='.$model->id.'&field=is_proxy_bet';
                                $provider = $proxyEnabled
                                    ? (TzSystemsUsers::PROXY_TYPE_OPTIONS[(int)$model->proxy_type] ?? '原代理')
                                    : '直连';
                                if((int)$model->proxy_type === 4 && method_exists($model, 'hasAttribute')
                                    && $model->hasAttribute('proxy_node_port') && (int)$model->proxy_node_port > 0){
                                    $provider .= ' · 节点 '.(int)$model->proxy_node_port;
                                }
                                $isAdmin = (int)Yii::$app->user->id === 1
                                    && Yii::$app->user->identity && (int)Yii::$app->user->identity->status === 10;
                                $providerHtml = Html::encode($provider);
                                if($isAdmin){
                                    $providerHtml .= ' '.Html::a('代理设置',
                                        ['/forum/proxy-nodes/index', 'id'=>$model->id],
                                        ['class'=>'btn btn-default btn-xs']);
                                }
                                $rows = [
                                    ['总开关', Html::a($flag($proxyEnabled), $proxyUrl,
                                        ['title'=>'开通使用代理IP', 'alt'=>$proxyEnabled ? '点击禁用' : '点击启用'])],
                                    ['登录', Html::a($flag((int)$model->is_proxy_login === 1), $loginUrl,
                                        ['title'=>'登录接口是否走代理'])],
                                    ['接口', Html::a($flag((int)$model->is_proxy_bet === 1), $betUrl,
                                        ['title'=>'非登录/下注接口是否走代理'])],
                                ];
                                if($isAdmin){
                                    $rows[] = ['代理商', $providerHtml];
                                }
                                return $summary($rows);
                            },
                        ],
                        ['attribute' => 'runtime_summary', 'label'=>'运行配置', 'format'=>'raw',
                            'contentOptions'=>['class'=>'admin-summary-cell'],
                            'value'=>function($model) use ($summary){
                                $flag = static function ($enabled) {
                                    return Html::tag('span', $enabled ? '是' : '否',
                                        ['class'=>$enabled ? 'text-success' : 'text-danger']);
                                };
                                $tls = Html::dropDownList(
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
                                $isLocal = (int)$model->is_local_bet !== BetsBackend::BET_TYPE_SERVER_API;
                                $cloud = Html::a('云服务器', [
                                    '/forum/user/switch-is-local-bet', 'id'=>$model->id,
                                    'status'=>BetsBackend::BET_TYPE_SERVER_API,
                                ], [
                                    'class'=>'btn btn-xs '.(!$isLocal ? 'btn-success' : 'btn-default'),
                                    'data'=>['method'=>'post', 'confirm'=>'确定切换到云服务器下注？'],
                                ]);
                                $local = Html::a('本地电脑', [
                                    '/forum/user/switch-is-local-bet', 'id'=>$model->id,
                                    'status'=>BetsBackend::BET_TYPE_LOCAL_API,
                                ], [
                                    'class'=>'btn btn-xs '.($isLocal ? 'btn-success' : 'btn-default'),
                                    'data'=>['method'=>'post', 'confirm'=>'确定切换到本地电脑下注？'],
                                ]);
                                $betLocation = Html::tag('div', $cloud.$local, ['class'=>'btn-group', 'role'=>'group']);
                                $followUrl = '/forum/user/switch-field-status?id='.$model->id.'&field=follow_status&status='.($model->follow_status ? 0 : 1);
                                $follow = Html::a($flag((int)$model->follow_status === 1), $followUrl,
                                    ['title'=>'自动跟开启']);
                                $autoLoginValue = (int)$model->is_auto_login === 1;
                                $autoLogin = $flag($autoLoginValue);
                                if($isLocal && !$autoLoginValue){
                                    $autoLogin = Html::tag('span', $autoLogin, ['title'=>'本地电脑下注不启用自动登']);
                                }else{
                                    $autoLoginUrl = '/forum/user/switch-auto-login?id='.$model->id.'&status='.($autoLoginValue ? 0 : 1);
                                    $autoLogin = Html::a($autoLogin, $autoLoginUrl, ['title'=>'自动登陆开启']);
                                }
                                $autoBetValue = (int)$model->is_auto_bet === 1;
                                $autoBetUrl = '/forum/user/switch-auto-bet-status?id='.$model->id.'&status='.($autoBetValue ? 0 : 1);
                                $autoBet = Html::a($flag($autoBetValue), $autoBetUrl, ['title'=>'自动下注脚本开启']);
                                return $summary([
                                    ['TLS', $tls],
                                    ['下注', $betLocation],
                                    ['自动跟', $follow],
                                    ['自动登', $autoLogin],
                                    ['自动下', $autoBet],
                                ]);
                            },
                        ],
                        ['attribute' => 'follow_summary', 'label'=>'跟投/时间', 'format'=>'raw',
                            'contentOptions'=>['class'=>'admin-summary-cell'],
                            'value'=> function($model) use ($summary, $textValue){
                                $follow = $model->flow_wp_accounts
                                    ? '['.$model->flow_wp_player_bs.'倍]正:'.$model->flow_wp_accounts : '';
                                $follow .= $model->flow_op_accounts
                                    ? ' ['.$model->flow_op_player_bs.'倍]反:'.$model->flow_op_accounts : '';
                                $updatedAt = (string)$model->update_time;
                                $updatedAt = $updatedAt === '' ? '' : substr($updatedAt, 5, -3);
                                return $summary([
                                    ['账号', $textValue($follow)],
                                    ['更新', $textValue($updatedAt)],
                                ]);
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
$this->registerCss(<<<'CSS'
.tz-systems-users-index .grid-view {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.tz-systems-users-index .grid-view th,
.tz-systems-users-index .grid-view td {
    vertical-align: top;
}
.tz-systems-users-index .admin-summary-cell {
    min-width: 132px;
}
.tz-systems-users-index .admin-login-cell {
    min-width: 72px;
    white-space: nowrap;
}
.tz-systems-users-index .admin-summary {
    line-height: 1.65;
    min-width: 118px;
}
.tz-systems-users-index .admin-summary-line {
    display: flex;
    align-items: flex-start;
    gap: 4px;
    white-space: normal;
}
.tz-systems-users-index .admin-summary-label {
    color: #888;
    flex: 0 0 42px;
    white-space: nowrap;
}
.tz-systems-users-index .admin-summary-value {
    min-width: 0;
    white-space: normal;
    overflow-wrap: anywhere;
}
.tz-systems-users-index .admin-summary .btn {
    margin: 1px 2px 1px 0;
}
@media (max-width: 767px) {
    .tz-systems-users-index .panel-body {
        padding: 10px;
    }
    .tz-systems-users-index .grid-view table {
        min-width: 760px;
        margin-bottom: 0;
        font-size: 12px;
    }
    .tz-systems-users-index .grid-view th,
    .tz-systems-users-index .grid-view td {
        padding: 6px 5px;
    }
    .tz-systems-users-index .admin-summary-cell {
        min-width: 142px;
        max-width: 220px;
    }
    .tz-systems-users-index .admin-login-cell {
        min-width: 74px;
    }
    .tz-systems-users-index .admin-summary .form-control {
        max-width: 126px;
        min-width: 96px;
        padding: 3px 5px;
        font-size: 12px;
    }
}
CSS
);
?>
