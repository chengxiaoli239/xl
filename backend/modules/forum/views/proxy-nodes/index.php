<?php
use yii\helpers\Html;
use backend\models\TzSystemsUsers;

$this->title = '代理节点管理';
$this->params['breadcrumbs'][] = $this->title;
$options = [''=>'请选择已启动的节点'];
foreach($nodes as $node){
    $options[$node['port']] = $node['name'].($node['running'] ? '（已启动）' : '（未启动）');
}
?>
<section class="wrapper site-min-height">
    <section class="panel">
        <header class="panel-heading">代理节点管理</header>
        <div class="panel-body">
            <p>导入和启动节点不会切换任何账号。只有单独保存账号的代理设置才会改变该账号后续请求的出口。</p>
            <?= Html::a('返回账号列表', ['/forum/user/view'], ['class'=>'btn btn-default']) ?>
            <?php if($error): ?><p class="alert alert-warning"><?= Html::encode($error) ?></p><?php endif; ?>
            <div id="node-message" role="status" aria-live="polite"></div>
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken(), ['id'=>'node-csrf']) ?>
            <h4>导入节点</h4>
            <?= Html::beginForm(['/forum/proxy-nodes/preview'], 'post', ['id'=>'node-import', 'enctype'=>'multipart/form-data']) ?>
                <div class="form-group">
                    <label for="subscription-url">订阅链接（返回 YAML 的 HTTPS 直链）</label>
                    <input id="subscription-url" name="url" type="password" autocomplete="new-password" class="form-control" placeholder="https://…">
                </div>
                <div class="form-group">
                    <label for="node-yaml">或上传 Clash YAML 文件（最大 1 MB）</label>
                    <input id="node-yaml" name="yaml" type="file" accept=".yaml,.yml">
                </div>
                <p class="help-block">只提取节点，不采用文件内的全局规则、DNS 或 TUN 设置。链接和节点密码不会显示在列表中。</p>
                <button type="submit" class="btn btn-primary">读取节点供选择</button>
            <?= Html::endForm() ?>
            <div id="node-preview" hidden>
                <h4>选择本次导入的节点</h4>
                <p>建议先选 1–2 个香港节点试用；节点名称仅供参考，以出口测试结果为准。总计最多 16 个节点，同时最多启动 8 个。</p>
                <div id="preview-options"></div>
                <button type="button" id="import-selected" class="btn btn-primary">导入所选节点</button>
            </div>
            <h4>已导入节点</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>名称</th><th>协议</th><th>状态</th><th>操作</th></tr></thead>
                    <tbody>
                    <?php foreach($nodes as $node): ?>
                        <tr>
                            <td><?= Html::encode($node['name']) ?></td>
                            <td><?= Html::encode($node['type']) ?></td>
                            <td><?= $node['running'] ? '已启动' : '未启动' ?></td>
                            <td>
                                <?php if(!$node['running']): ?>
                                    <button class="btn btn-default btn-sm node-start" data-port="<?= (int)$node['port'] ?>">启动</button>
                                <?php else: ?>
                                    <button class="btn btn-default btn-sm node-test" data-port="<?= (int)$node['port'] ?>">测试出口 IP / 地区</button>
                                <?php endif; ?>
                                <span class="node-result" role="status"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(!$nodes): ?><tr><td colspan="4">尚未导入节点。现有账号继续使用原来的网络设置。</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if($account): ?>
            <h4>当前账号：<?= Html::encode($account->username) ?>（盘口 <?= (int)$account->tz_system_id ?>）</h4>
            <?= Html::beginForm(['/forum/tz-systems-users/set-proxy-type'], 'post', ['id'=>'account-proxy']) ?>
                <?= Html::hiddenInput('id', $account->id) ?>
                <div class="form-group">
                    <?= Html::label('此账号是否使用代理', 'account-proxy-enabled') ?>
                    <?= Html::dropDownList('is_use_proxy', (int)$account->is_use_proxy, [0=>'关闭：按原直连路径', 1=>'开启：按下面的代理商和场景'], ['id'=>'account-proxy-enabled','class'=>'form-control']) ?>
                </div>
                <div class="form-group">
                    <?= Html::label('代理商', 'account-proxy-type') ?>
                    <?= Html::dropDownList('proxy_type', (int)$account->proxy_type, TzSystemsUsers::PROXY_TYPE_OPTIONS, ['id'=>'account-proxy-type','class'=>'form-control']) ?>
                </div>
                <div class="form-group" id="account-node-field">
                    <?= Html::label('此账号独立使用的节点', 'account-node') ?>
                    <?= Html::dropDownList('proxy_node_port', $account->hasAttribute('proxy_node_port') ? (int)$account->proxy_node_port : '', $options, ['id'=>'account-node','class'=>'form-control']) ?>
                    <p class="help-block">先启动并测试出口，再保存账号设置。这里只改变当前账号，其他账号绑定不变。</p>
                </div>
                <?php foreach(['is_proxy_login'=>'登录请求走代理', 'is_proxy_bet'=>'后续接口及下注请求走代理'] as $field=>$label): ?>
                <div class="form-group">
                    <?= Html::label($label, 'account-'.$field) ?>
                    <?= Html::dropDownList($field, (int)$account->$field, [0=>'关闭',1=>'开启'], ['id'=>'account-'.$field,'class'=>'form-control']) ?>
                </div>
                <?php endforeach; ?>
                <p>建议确认此账号没有执行中的请求后切换。出口变化可能需要重新登录盘口；系统不会自动登录或执行测试下注。</p>
                <p>目前独立节点适用于盘口 9/10 的 Lucky5 云服务器账号；本地客户端和旧七时彩接口不适用。</p>
                <button class="btn btn-primary" type="submit">仅保存当前账号</button>
                <span id="account-result" role="status"></span>
            <?= Html::endForm() ?>
            <?php else: ?><p>从账号列表的“代理设置”进入，可为指定账号单独选择节点。</p><?php endif; ?>
        </div>
    </section>
</section>
<?php
$this->registerJs(<<<'JS'
var previewTicket = '';
function nodeMessage(message) { $('#node-message').text(message).attr('class', 'alert alert-info'); }
function nodePost(url, data, button, success) {
    var token = $('#node-csrf');
    if (typeof data === 'string') { data += '&' + encodeURIComponent(token.attr('name')) + '=' + encodeURIComponent(token.val()); }
    else { data[token.attr('name')] = token.val(); }
    button.prop('disabled', true);
    $.post(url, data, function (rst) {
        if (rst.status === 200) { success(rst.data || rst); }
        else { nodeMessage(rst.msg || '操作失败，原账号设置未改变'); }
    }, 'json').fail(function () { nodeMessage('请求未完成，请刷新确认状态后再操作'); })
        .always(function () { button.prop('disabled', false); });
}
$('#node-import').on('submit', function (event) {
    event.preventDefault();
    var button = $(this).find('button');
    button.prop('disabled', true);
    previewTicket = '';
    $('#node-preview').prop('hidden', true);
    $.ajax({url:this.action, type:'POST', data:new FormData(this), processData:false, contentType:false, dataType:'json'})
        .done(function (rst) {
            if (rst.status !== 200) { nodeMessage(rst.msg || '读取失败'); return; }
            previewTicket = rst.data.ticket;
            $('#preview-options').empty();
            $.each(rst.data.nodes, function (_, node) {
                var label = $('<label>').addClass('checkbox');
                label.append($('<input>', {type:'checkbox', value:node.index}));
                label.append(document.createTextNode(node.name + ' (' + node.type + ')'));
                $('#preview-options').append(label);
            });
            $('#node-preview').prop('hidden', false);
            $('#subscription-url').val(''); $('#node-yaml').val('');
            nodeMessage('读取成功，请选择要导入的节点；尚未改变任何账号');
        }).fail(function () { nodeMessage('读取失败，请检查文件大小和网络'); })
        .always(function () { button.prop('disabled', false); });
});
$('#import-selected').on('click', function () {
    var indexes = $('#preview-options input:checked').map(function () { return this.value; }).get();
    if (!indexes.length || indexes.length > 16) { nodeMessage('请选择 1–16 个节点'); return; }
    nodePost('/forum/proxy-nodes/import', {ticket:previewTicket,indexes:indexes}, $(this), function () { window.location.reload(); });
});
$('.node-start').on('click', function () {
    nodePost('/forum/proxy-nodes/start', {port:$(this).data('port')}, $(this), function () { window.location.reload(); });
});
$('.node-test').on('click', function () {
    var result = $(this).siblings('.node-result');
    result.text('');
    nodePost('/forum/proxy-nodes/test', {port:$(this).data('port')}, $(this), function (data) {
        result.text('出口 ' + data.ip + '，地区 ' + data.country + (data.country === 'HK' ? '（香港）' : ''));
    });
});
function toggleNode() { $('#account-node-field').toggle($('#account-proxy-type').val() === '4'); }
$('#account-proxy-type').on('change', toggleNode); toggleNode();
$('#account-proxy').on('submit', function (event) {
    event.preventDefault();
    if (!window.confirm('仅保存当前账号的代理和节点设置？请确认该账号无执行中请求，切换出口后可能需要重新登录。')) { return; }
    nodePost(this.action, $(this).serialize(), $(this).find('button'), function () {
        $('#account-result').text('当前账号设置已保存，其他账号未改变');
    });
});
JS
);
