<link rel="stylesheet" href="/vendors/layui/2.5.4/css/layui.css?v=2020">
<link rel="stylesheet" href="/css/layui/global.css?v={{STATIC_VERSION}}">
<link rel="stylesheet" href="//at.alicdn.com/t/font_1529192_39xhl8um9u9.css">
<script type="text/javascript" src="/vendors/layui-layer/3.1.1/layer.js"></script>
<script type="text/javascript" src="/vendors/layui/2.4.5/layui.js"></script>
<script type="text/javascript" src="/vendors/atrtemplate/4.13.2/template-web.js"></script>
<script type="text/javascript" src="/statics/js/jquery-2.0.3.js"></script>
<script type="text/javascript" src="/js/layui/global.js?v={{STATIC_VERSION}}"></script>
<script type="text/javascript" src="/js/common.js?v={{STATIC_VERSION}}"></script>
<style>
.red-text {
    color: red;              /* 字体颜色为红色 */
    font-size: 14px;        /* 字体大小为12号 */
    background-color: white; /* 背景颜色为白色 */
}
.chat-trigger {
    position: fixed;
    right: 20px;
    top: 50%;
    width: 50px;
    height: 50px;
    background: #1E9FFF;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: move;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    touch-action: none;
    user-select: none;
}

.deepseek-chat {
    position: fixed;
    display: none;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 450px;
    height: 70vh;
    max-height: 700px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1001;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.deepseek-chat.active {
    display: block;
    opacity: 1;
}

.chat-trigger.hidden {
    display: none;
}

@media (max-width: 768px) {
    .deepseek-chat {
        width: 90%;
        height: 80vh;
        max-height: none;
    }
}

.chat-header {
    padding: 15px 20px;
    background: #1E9FFF; /* 深色背景 */
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header span {
    color: #fff;
    font-size: 16px;
    font-weight: 500;
}

.chat-close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}


.chat-close:hover {
    background: rgba(255,255,255,0.3);
}

/* 移动端适配 */
@media (max-width: 768px) {
    .deepseek-chat {
        width: 90%;
        height: 80vh;
        left: 5%;
        right: 5%;
    }
}


.chat-body {
    height: calc(100% - 65px);
    display: flex;
    flex-direction: column;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
}

.chat-input-container {
    padding: 15px;
    border-top: 1px solid #eee;
    background: #fff;
    border-radius: 0 0 8px 8px;
}

.chat-input-wrapper {
    display: flex;
    gap: 10px;
}

#userInput {
    flex: 1;
    height: 40px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: none;
    font-size: 14px;
}

.send-button {
    width: 80px;
    height: 40px;
    background: #1E9FFF;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.send-button:hover {
    background: #0d8aff;
}
</style>
<?php

use backend\models\PlanStaticProfits;
use backend\service\SscDataService;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\BaseStringHelper;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\UserSysPlans */
/* @var $dataProvider yii\data\ActiveDataProvider */
$this->title = Yii::t('app', 'User Sys Plans');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
$columns = array_merge(
    [
        ['class' => 'yii\grid\CheckboxColumn', 'headerOptions'=>['width'=>'2%']],

        ['attribute' => 'playway','headerOptions'=>['width'=>'5%'],'label'=>'类型1',
            'format'=>'raw',
            'value' => function($model) {
                $playWayArr = [1=>'二字定', 2=>'三字定', 3=>'四字定', 4=>'一字定', 6=>'X字现'];

                $url = '/forum/betting-records/index?BettingRecords[plan_id]='.$model->id;
                $remark = '<br>'.Html::a($model->remark?'[备:'.$model->remark.']':'[备注]', 'javascript:;', ['class'=>'set_remark_pop', 'id'=>'remark_plan_id_'.$model->id, 'data-id'=>$model->id, 'data-remark'=>$model->remark]);
                return $playWayArr[$model->playway].'['.Html::a($model->id, $url).']'.$remark;
            }
        ],

        ['attribute' => 'tz_type','label'=>'类型2', # 'headerOptions'=>['width'=>'5%'],
            'value' => function($model) {
                if($model->playway == 2 && in_array($model->tz_type, [1,2,3])){
                    //投注类型:1大小单双三字定2大小三字定3单双三字定
                    $tz_type_Arr = [1=>'大小单双三字定', 2=>'大小三字定', 3=>'单双三字定'];
                    $typeName = $tz_type_Arr[$model->tz_type];
                }elseif(in_array($model->playway, [1,2,3,4]) OR in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
                    $typeName = \backend\service\BetService::getTypeNameByTzType($model->tz_type);
                }
                if(in_array($model->tz_type, \Yii::$app->params['IS_XIAN'])){
                    $xians = [36=>'二字现', 17=>'三字现', 37=>'四字现'];
                    return $xians[$model->tz_type];
                }
                return str_replace('三定-', '', str_replace('四定-', '', $typeName));
            }
        ],
        //'buy_type',
        ['attribute' => 'buy_type','label'=>'正/反',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value' => function($model) {
                $buy_type_Arr = [0=>'反买', 1=>'正买'];
                $txt = $buy_type_Arr[$model->buy_type];
                $url0 = "/forum/user-sys-plans/switch-buy-type?id=".$model->id.'&status=1'; # 切换正买
                $url1 = "/forum/user-sys-plans/switch-buy-type?id=".$model->id.'&status=0'; # 切换反买
                if($model->tz_type==22){  # 四定单双
                    //return Html::a($txt, '#', ['title' => '正买']);
                    return '';
                }
                if($model->buy_type == 1){
                    $txt = "<font color='green'>$txt</font>" ;
                    return Html::a($txt, $url1, ['title' => '切换反买']);
                }
                if(!$model->buy_type){
                    $txt = "<font color='red'>$txt</font>";
                    return Html::a($txt, $url0, ['title' => '切换正买']);
                }
            }
        ],
        ['attribute' => 'plan_type','label'=>'计划类型', 'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value' => function($model) {
                $txt = \backend\service\TzService::getTzPlanTypes($model->plan_type).'_';
                $url0 = "/forum/user-sys-plans/switch-test?id=".$model->id.'&status=1'; # 切换真实
                $url1 = "/forum/user-sys-plans/switch-test?id=".$model->id.'&status=0'; # 切换模拟
                if($model->is_test) {
                    $txt .= Html::a("<font color='gray'><strong>模拟</strong></font>", $url0, ['title' => '切换真实']);
                }else{
                    $txt .= Html::a("<font color='green'><strong>真实</strong></font>", $url1, ['title' => '切换模拟']);
                }
                return $txt;
            }
        ],
        //'status',
        ['attribute'=>'status', 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value'=>function($model){
                $url0 = "/forum/user-sys-plans/switch-status?id=".$model->id.'&status=1'; # 点击开启
                $url1 = "/forum/user-sys-plans/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                if($model->status == 1){
                    $txt = "<font color='green'>已开启</font>";
                    return Html::a($txt, $url1, ['title' => '点击关闭']).'<i class="icon-refresh"></i>';
                }
                if(!$model->status){
                    $txt = "<font color='red'>已关闭</font>";
                    return Html::a($txt, $url0, ['title' => '点击开启']).'<i class="icon-refresh"></i>';
                }
                //return $model->snid;
            }
        ],
        //'single',
        ['attribute' => 'single','headerOptions'=>['width'=>'5%'],'label'=>'倍(元)',
            'value' => function($model) {
                return $model->single;
            }
        ],
        ['attribute' => 'current_profits','label'=>'当前',
            'format'=>'raw',
            'value' => function($model) {
                if(in_array($model->plan_type,[1, 3]) OR ($model->take_profits>0 OR $model->stop_loss)){
                    $currentProfits = PlanStaticProfits::find()->select(['cut_profits'])->where(['plan_id'=>$model->id])->scalar()?:0.00;
                    $txt = '止盈:'.floatval($model->take_profits)." 止损:".floatval($model->stop_loss) .' 当前:<font data-profits="'.$currentProfits.'" color="'.(($model->current_profits>0)?'green':($model->current_profits<0?'red':'')).'">'.round($currentProfits, 2).'</font>' ;
                }else{
                    $txt = '';
                }
                return $txt;
            }
        ],
        ['attribute' => 'tz_type','label'=>'操作', # 'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value' => function($model) {
                $url = "/forum/user-sys-plans/tz-now?id=".$model->id; # 立即下注
                $txt = Html::a('立即下注', $url, ['title' => '立即下注'.$model->id,'alt'=>$model->id]);
                if(in_array($model->plan_type,[1, 3]) OR ($model->take_profits>0 OR $model->stop_loss)){
                    $url1 = "/forum/user-sys-plans/re-calculate-profits?id=".$model->id; # 重新计算盈利
                    $txt .= ' | '.Html::a('重算盈利', $url1, ['title' => '重算盈利'.$model->id,'alt'=>$model->id]);
                }

                return $txt;
            }
        ],
        //'tz_sites',
        ['attribute' => 'tz_sites','label'=>'站点',#'headerOptions'=>['width'=>'5%'],
            'value' => function($model) {
                $site_ids = explode(',',$model->tz_sites);
                $str = '';
                foreach ($site_ids as $site_id){
                    $str .= ','.\backend\models\TzSystems::findOne($site_id)->name;
                }
                return trim($str,',');
            }
        ]
    ],
    \Yii::$app->user->identity['user_type']!=\common\models\AdminModel::USER_TYPE_SUPER_ADMIN?[]:[
        ['attribute' => 'account','headerOptions'=>['width'=>'5%'],'label'=>'账号',
            'format'=>'raw',
            'value' => function($model) {
                return Html::a($model->account, '/forum/user-sys-plans/index?UserSysPlans[account]='.$model->account);
            }
        ],
    ],
    [
        ['attribute' => 'hz_Arr','label'=>'扩展',#'headerOptions'=>['width'=>'5%'],
            'format'=>'raw',
            'value' => function($model) {
                if(\backend\service\BaseService::is_json($model->hz_Arr) OR in_array($model->tz_type, [18, 19, 20, 23, 25, 27, 28, 29, 30, 31, 32, 33, 34, 17,36,37])){
                    $str = \backend\service\NumService::getDescByKuaixuan(json_decode($model->hz_Arr, true), $model->id);
                    $desc_str = $str;
                    if(in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
                        $title = \backend\models\ImportPlanCodes::findOne(['plan_id'=>$model->id])->codes;
                    }
                    $codes_hz = json_decode($model->hz_Arr, true);
                    $betStatusTxt = ($codes_hz['areaBetStatus']==1 OR $codes_hz['betStatus']==1) ? '正在下注...' : '等待中...';
                }else{
                    $title = $model->hz_Arr;
                }
                $txt = BaseStringHelper::truncate($str,24);
                $desc_str .= !empty($model->singles) ? '翻倍：'.$model->singles : '';
                $str = Html::a($txt, 'javascript:;', ['title' => \backend\service\SscDataService::getCodesDesc($title),'alt'=>\backend\service\SscDataService::getCodesDesc($desc_str), 'class'=>'act-desc', 'plan_id'=>$model->id, 'current_profits'=>round($model->current_profits, 2), 'betStatusTxt'=>$betStatusTxt]);
                if(!empty($model->singles) OR in_array($model->plan_type,[2, 3, 4, 5, 9, 10])){
                    $str .= '翻倍梯度:'.$model->singles;
                }
                return $str;
            }
        ],
        ['attribute' => 'updated_at','label'=>'时间',
            'value' => function($model) {
                return date('m-d H:i', $model->updated_at);
            }
        ],
        /*
        [
            'class' => 'yii\grid\ActionColumn',
            'headerOptions' => ['width' => '5%'],
            'template' => '{update} &nbsp;&nbsp;&nbsp;&nbsp; {delete}',
            'buttons' => [
                'update' => function ($url, $model) {
                    return Html::a('更新', $url, ['class' => 'btn btn-primary btn-xs', 'title' => '更新']);
                },
                'delete' => function ($url, $model) {
                    return '<br><br>' . Html::a('删除', 'javascript:void(0);', [
                            'class' => 'btn btn-danger btn-xs delete-button',
                            'title' => '删除',
                            'data-url' => $url,
                            'data-confirm' => '确认要删除该项吗?',
                        ]);
                },
            ],
        ],
        */
        ['class' => 'yii\grid\ActionColumn','headerOptions'=>['width'=>'5%'],'template'=>'{update}&nbsp;&nbsp;&nbsp;&nbsp;{delete}'],
    ]
);
?>
<section class="user-sys-plans-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title); ?>
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <span><strong>添加计划：</strong></span>
                    <!--div class="btn-group">
                        <?= Html::a(Yii::t('app', 'User Sys Plans 2d'), ['create', 'playway'=>2, 'tz_type'=>16, 'lottery_type'=>$lottery_type], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div-->

                    <!--div class="btn-group">
                        <?= Html::a(Yii::t('app', 'User Sys Plans 3d'), ['create', 'playway'=>3, 'lottery_type'=>$lottery_type], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div-->
                    <?php foreach ($myTzTypes as $typeData):?>
                    <div class="btn-group">
                        <?= Html::a($typeData['type_name'], ['create', 'tz_type'=>$typeData['tz_type'], 'lottery_type'=>$typeData['lottery_type']], ['class' => 'btn btn-success btn-sm', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <?endforeach;?>
                </div>

                <?php echo $this->render('_search', ['model' => $searchModel]); ?>
                <div class="operation-row" style="margin-bottom: 15px;">
                    <div class="btn-group">
                        <?= Html::button("批量关闭", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchClose']) ?> &nbsp;
                        <?= Html::button("批量开启", ['class' => 'btn btn-success btn-xs', 'id' => 'batchOpen']) ?> &nbsp;
                        <?= Html::button("批量删除", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchDelete']) ?> &nbsp;
                        <?= Html::button("批量真实", ['class' => 'btn btn-success btn-xs', 'id' => 'batchTrue']) ?> &nbsp;
                        <?= Html::button("批量模拟", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchSimulate']) ?> &nbsp;
                        <?= Html::button("批量正", ['class' => 'btn btn-success btn-xs', 'id' => 'batchForward']) ?> &nbsp;
                        <?= Html::button("批量反", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchReverse']) ?>
                        <?= Html::button("批量修改类型", ['class' => 'btn btn-primary btn-xs', 'id' => 'batchUpdatePlanType']) ?>
                    </div>
                    <div class="pull-right">
                        <?= Html::a($tipTxt, 'javascript:;', ['class' => 'btn red-text']) ?>
                        <?= Html::tag('span', '总盈利: ' . number_format($all_profits, 2), [
                            'class' => 'btn', 
                            'style' => 'color:' . ($all_profits > 0 ? 'green' : ($all_profits < 0 ? 'red' : 'black')) . 
                                      ';font-weight:bold;margin-left:10px;'
                        ]) ?>
                    </div>
                </div>

                <div style="clear:both;"></div>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'options' => ['class' => 'grid-view table-responsive'],
                    //'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    #'filterModel' => $searchModel,
                    'columns' => $columns,
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
    <!-- 悬浮方块按钮 -->
    <div class="chat-trigger" onclick="toggleChat()">
        <i class="layui-icon layui-icon-dialogue"></i>
    </div>

    <!-- 聊天窗口 -->
    <div class="deepseek-chat" id="deepseekChat">
        <div class="chat-header">
            <span>智能投注助手</span>
            <button class="chat-close" onclick="toggleChat()">×</button>
        </div>
        <div class="chat-body">
            <div class="chat-messages" id="chatMessages">
                <!-- 消息会动态添加到这里 -->
            </div>
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <textarea id="userInput" placeholder="请描述您的投注需求..."></textarea>
                    <button class="send-button" onclick="sendMessage()">发送</button>
                </div>
            </div>
        </div>
    </div>
</section>
<!--提示框-start-->
<div class="modal fade " id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 800px;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>计划描述：</strong>
                    <pre><code id="rst_code"></code></pre>
                    <strong>计划内容：</strong>
                    <pre><code id="push_content"></code></pre>
                </form>
            </div>
            <!--div class="form-group down-reason">
                <p><label>备注信息:</label><input class="form-control" id="message" name="message" /></p>
            </div-->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade " id="exampleModal_set_remark" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 500px;margin: 10px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="remark_title">计划备注：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_remark" style="display:block; width:95%;height: 200px;overflow-y: scroll">
                    <p><label>备注信息:</label><textarea rows="5" class="form-control" id="remark_text" name="remark_text" ></textarea></p>
                </form>
            </div>
            <!--div class="form-group down-reason">
                <p><label>备注信息:</label><input class="form-control" id="message" name="message" /></p>
            </div-->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_set_remark">确定</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="batchUpdatePlanTypeModal" tabindex="-1" role="dialog" aria-labelledby="batchUpdatePlanTypeModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="batchUpdatePlanTypeModalLabel">批量修改计划类型</h4>
            </div>
            <div class="modal-body">
                <form id="batchUpdatePlanTypeForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-horizontal">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label class="col-sm-3 control-label">计划类型：</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="newPlanType" name="newPlanType" style="width: 200px; display: inline-block;">
                                            <?php foreach (SscDataService::PLAN_TYPE_OPTIONS as $key => $value): ?>
                                                <option value="<?= $key ?>"><?= $value ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">计划ID：</label>
                                    <div class="col-sm-9">
                                        <div id="selectedPlanIds" class="form-control-static" style="word-break: break-all; padding-top: 7px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmBatchUpdatePlanType">确认</button>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="operate_id" name="operate_id" value="">
<!--提示框-end-->
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
    $(document).ready(function() {
        $('#batchUpdatePlanType').click(function() {
            var selectedIds = $('input[name="selection[]"]:checked').map(function() {
                return this.value;
            }).get();

            if (selectedIds.length <= 0) {
                Ewin.confirm({ message: '至少选择一项' });
                return;
            }

            // Update the selected IDs display
            $('#selectedPlanIds').html(selectedIds.join(', '));

            $('#batchUpdatePlanTypeModal').modal('show');
        });

        $('#confirmBatchUpdatePlanType').click(function() {
            const selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();
            const newPlanType = $('#newPlanType').val();

            if (selectedIds.length <= 0) {
                alert('至少选择一项');
                return;
            }
            const planTypeName = $('#newPlanType option:selected').text();


            // 添加更详细的确认对话框
            layer.confirm(`确认类型修改为：${planTypeName}`, {
                btn: ['确认修改','取消']
            }, function(){
                // 用户点击确认
                $.post('/forum/user-sys-plans/batch-update-plan-type',
                    { ids: selectedIds, newPlanType: newPlanType },
                    function(response) {
                        if (response.status === 200) {
                            layer.msg('修改成功');
                            location.reload();
                        } else {
                            layer.alert('批量操作失败: ' + response.message);
                        }
                    }, 'json'
                );
            });
        });
    });
    $(function () {
        $(".act-desc").click(function (rst) {
            bet_rst = $(this).attr('alt');
            content = $(this).attr('title');
            plan_id = $(this).attr('plan_id');
            betStatusTxt = $(this).attr('betStatusTxt');
            current_profits = parseFloat($(this).attr('current_profits'));

            push_desc = {"plan_id":plan_id, "desc":bet_rst, "all_profits":current_profits, 'status_txt':betStatusTxt}
            push_content = {"desc":bet_rst, "detail":content};
            $('#rst_code').text(JSON.stringify(push_desc, null,' '))
            $('#push_content').text(JSON.stringify(push_content,null,' '))

            $('#exampleModal_msg').modal('show');
        });

        // Batch update status
        $('#batchOpen').click(function () {
            var selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();

            batchUpdate('status', selectedIds, 1);
        });

        // Batch close status
        $('#batchClose').click(function () {
            var selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();

            batchUpdate('status', selectedIds, 0);
        });
        // Batch delete status
        $('#batchDelete').click(function () {
            var selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();

            batchUpdate('status', selectedIds, -2);
        });
        $('#batchTrue').click(function () {
            var selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();

            batchUpdate('is_test', selectedIds, 0);
        });
        $('#batchSimulate').click(function () {
            var selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();

            batchUpdate('is_test', selectedIds, 1);
        });
        $('#batchForward').click(function () {
            var selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();

            batchUpdate('buy_type', selectedIds, 1);
        });
        $('#batchReverse').click(function () {
            var selectedIds = $('input[name="selection[]"]:checked').map(function () {
                return this.value;
            }).get();

            batchUpdate('buy_type', selectedIds, 0);
        });
        function batchUpdate(field, ids, val) {
            console.log(field, ids, val)
            if (ids.length <= 0) {
                layer.alert('至少选择一项')
            }
            // Perform AJAX request to update the selected items
            $.post('/forum/user-sys-plans/batch-switch-status', { field: field, ids: ids , val: val}, function (response) {
                if (response.status === 200) {
                    layer.alert(val===-2?'删除成功':'更新成功', function(index){
                        layer.close(index); // Close the alert
                        setTimeout(function(){
                            location.reload(); // Reload the current page after 2 seconds
                        }, 1000); // 2000 milliseconds (2 seconds)
                    });
                } else {
                    layer.alert('批量操作失败.');
                }
            }, 'json');
        }

        $(".set_remark_pop").click(function (rst) {
            id = $(this).data('id');
            $('#remark_title').html('计划ID：'+id+'，修改备注')
            $('#remark_text').val($(this).data('remark'))
            $('#operate_id').val(id)

            $('#exampleModal_set_remark').modal('show');
        });

        $("#confirm_set_remark").click(function (rst) {
            remark = $('#remark_text').val()
            id = $('#operate_id').val()

            console.log(id, remark)
            $.post('/forum/user-sys-plans/update-desc', {id:id, remark:remark}, function (response) {
                if(response.status === 200){
                    // 这里使用Layer UI的提示来确认刷新
                    var remarkId = '#remark_plan_id_' + id;
                    $(remarkId).html('备:'+$('#remark_text').val())
                    // 为该元素添加 class，使字体颜色变为红色
                    $(remarkId).addClass('red-text'); // 为 ID 对应的元素添加类

                    /*
                    layer.confirm('修改成功，计划ID:'+id+'，备注：' + remark, {
                        btn: ['是', '否'] //按钮
                    }, function(){
                        // 刷新页面
                        location.reload();
                    }, function(){
                        // 如果选“否”，就不做任何事情
                        layer.closeAll(); // 关闭所有层
                    });
                     */
                }
            });
        });
    });
    $(document).one('click', '.delete-button', function () {
        var url = $(this).data('url'); // 获取删除的URL
        var confirmMessage = $(this).data('confirm'); // 获取确认信息

        layui.use('layer', function () {
            var layer = layui.layer;
            layer.confirm(confirmMessage, {
                btn: ['确定', '取消'] //按钮
            }, function () {
                // 点击“确定”，执行删除
                $.post(url, function (data) {
                    if (data.success) {
                        // 刷新页面或移除对应的行
                        location.reload();
                    } else {
                        layer.alert('删除失败: ' + data.message);
                    }
                }).fail(function() {
                    layer.alert('删除请求失败，请重试。');
                });
            }, function () {
                // 点击“取消”，什么都不做
            });
        });
    });

    function makeDraggable(element) {
        let startX, startY, initialX, initialY;
        let active = false;
        let lastTap = 0;

        function init() {
            element.addEventListener('mousedown', startDragging);
            element.addEventListener('touchstart', startDragging, { passive: false });
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('mouseup', stopDragging);
            document.addEventListener('touchend', stopDragging);
        }

        function startDragging(e) {
            if (e.type === 'mousedown') {
                initialX = e.clientX - element.offsetLeft;
                initialY = e.clientY - element.offsetTop;
            } else {
                initialX = e.touches[0].clientX - element.offsetLeft;
                initialY = e.touches[0].clientY - element.offsetTop;
            }

            if (e.target === element) {
                active = true;
                startX = e.type === 'mousedown' ? e.clientX : e.touches[0].clientX;
                startY = e.type === 'mousedown' ? e.clientY : e.touches[0].clientY;
            }

            e.preventDefault();
        }

        function drag(e) {
            if (!active) return;

            e.preventDefault();

            let currentX = e.type === 'mousemove' ? e.clientX : e.touches[0].clientX;
            let currentY = e.type === 'mousemove' ? e.clientY : e.touches[0].clientY;

            let deltaX = currentX - initialX;
            let deltaY = currentY - initialY;

            // 限制在视窗范围内
            const maxX = window.innerWidth - element.offsetWidth;
            const maxY = window.innerHeight - element.offsetHeight;
            deltaX = Math.min(Math.max(0, deltaX), maxX);
            deltaY = Math.min(Math.max(0, deltaY), maxY);

            element.style.left = `${deltaX}px`;
            element.style.top = `${deltaY}px`;
        }

        function stopDragging(e) {
            if (!active) return;

            let currentX = e.type === 'mouseup' ? e.clientX : e.changedTouches[0].clientX;
            let currentY = e.type === 'mouseup' ? e.clientY : e.changedTouches[0].clientY;

            let moved = Math.abs(currentX - startX) > 5 || Math.abs(currentY - startY) > 5;

            if (!moved) {
                // 如果没有移动，则触发点击
                toggleChat(e);
            }

            active = false;
        }

        init();
    }

    function toggleChat(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        const chatWindow = document.getElementById('deepseekChat');
        const chatTrigger = document.querySelector('.chat-trigger');

        if (!chatWindow.classList.contains('active')) {
            // 显示聊天窗口
            chatWindow.style.display = 'block';
            setTimeout(() => {
                chatWindow.classList.add('active');
                chatTrigger.classList.add('hidden');
            }, 10);

            // 添加点击外部关闭功能
            document.addEventListener('click', closeOnClickOutside);
        } else {
            closeChat();
        }
    }

    function closeChat() {
        const chatWindow = document.getElementById('deepseekChat');
        const chatTrigger = document.querySelector('.chat-trigger');

        chatWindow.classList.remove('active');
        setTimeout(() => {
            chatWindow.style.display = 'none';
            chatTrigger.classList.remove('hidden');
        }, 300);

        document.removeEventListener('click', closeOnClickOutside);
    }

    function closeOnClickOutside(e) {
        const chatWindow = document.getElementById('deepseekChat');
        const chatTrigger = document.querySelector('.chat-trigger');

        if (!chatWindow.contains(e.target) && !chatTrigger.contains(e.target)) {
            closeChat();
        }
    }

    // 初始化layer
    layui.use(['layer'], function(){
        var layer = layui.layer;
    });

    document.addEventListener('DOMContentLoaded', function() {
        const chatTrigger = document.querySelector('.chat-trigger');
        if (chatTrigger) {
            // 设置初始位置
            chatTrigger.style.position = 'fixed';
            chatTrigger.style.left = 'auto';
            chatTrigger.style.right = '20px';
            chatTrigger.style.top = '50%';

            makeDraggable(chatTrigger);
        }

        // 添加 ESC 键关闭功能
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeChat();
            }
        });
    });

    async function sendMessage() {
        const userInput = document.getElementById('userInput');
        const message = userInput.value.trim();
        if (!message) return;

        // 添加用户消息
        addMessage(message, 'user');
        userInput.value = '';

        try {
            // 调用后端API
            const response = await fetch('/forum/deepseek/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();
            
            // 添加机器人回复
            addMessage(data.message, 'bot');

            // 如果有投注建议，显示可点击的建议
            if (data.suggestions) {
                data.suggestions.forEach(suggestion => {
                    addSuggestion(suggestion);
                });
            }
        } catch (error) {
            console.error('Error:', error);
            addMessage('抱歉，服务出现错误，请稍后重试。', 'bot');
        }
    }

    function addMessage(message, type) {
        const chatMessages = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}-message`;
        messageDiv.textContent = message;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function addSuggestion(suggestion) {
        const chatMessages = document.getElementById('chatMessages');
        const suggestionDiv = document.createElement('div');
        suggestionDiv.className = 'plan-suggestion';
        suggestionDiv.innerHTML = `
            推荐方案: ${suggestion.name}<br>
            胜率: ${suggestion.winRate}%<br>
            <button onclick="addToPlan(${JSON.stringify(suggestion)})">添加到计划</button>
        `;
        chatMessages.appendChild(suggestionDiv);
    }

    function addToPlan(suggestion) {
        // 调用创建计划的接口
        $.post('/forum/user-sys-plans/create-from-suggestion', suggestion, function(response) {
            if (response.status === 200) {
                layer.msg('计划添加成功');
                location.reload();
            } else {
                layer.msg('计划添加失败：' + response.message);
            }
        });
    }
</script>