<link rel="stylesheet" href="/vendors/layui/2.5.4/css/layui.css?v=2020">
<link rel="stylesheet" href="/css/layui/global.css?v={{STATIC_VERSION}}">
<script type="text/javascript" src="/vendors/layui-layer/3.1.1/layer.js"></script>
<script type="text/javascript" src="/vendors/layui/2.4.5/layui.js"></script>
<script type="text/javascript" src="/vendors/atrtemplate/4.13.2/template-web.js"></script>
<script type="text/javascript" src="/statics/js/jquery-2.0.3.js"></script>
<script type="text/javascript" src="/js/layui/global.js?v={{STATIC_VERSION}}"></script>
<script type="text/javascript" src="/js/common.js?v={{STATIC_VERSION}}"></script>
<style>
    .red-text {
        color: red;              /* 字体颜色为红色 */
        font-size: 16px;        /* 字体大小为12号 */
        background-color: white; /* 背景颜色为白色 */
    }
</style>
<?php

use backend\models\PlanStaticProfits;
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
                $remark = Html::a('[备注:'.$model->desc.']', 'javascript:;', ['class'=>'set_remark_pop', 'data-id'=>$model->id, 'data-desc'=>$model->desc]);
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

                <?php if(\Yii::$app->user->id == 1) echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= Html::button("批量关闭", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchClose']) ?> &nbsp;
                <?= Html::button("批量开启", ['class' => 'btn btn-success btn-xs', 'id' => 'batchOpen']) ?> &nbsp;
                <?= Html::button("批量删除", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchDelete']) ?> &nbsp;
                <?= Html::button("批量真实", ['class' => 'btn btn-success btn-xs', 'id' => 'batchTrue']) ?> &nbsp;
                <?= Html::button("批量模拟", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchSimulate']) ?> &nbsp;
                <?= Html::button("批量正", ['class' => 'btn btn-success btn-xs', 'id' => 'batchForward']) ?> &nbsp;
                <?= Html::button("批量反", ['class' => 'btn btn-danger btn-xs', 'id' => 'batchReverse']) ?> &nbsp;

                <?= Html::a($tipTxt, 'javascript:;', ['class' => 'btn red-text']) ?> &nbsp;
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => $columns,
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
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
<input type="hidden" id="operate_id" name="operate_id" value="">
<!--提示框-end-->
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
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
            content = $(this).data('desc');
            id = $(this).data('id');
            console.log(id, content)
            $('#remark_text').html(content)
            $('#operate_id').val(id)

            $('#exampleModal_set_remark').modal('show');
        });

        $("#confirm_set_remark").click(function (rst) {
            content = $('#remark_text').val()
            id = $('#operate_id').val()
            console.log(content, id)

            $.post('/forum/user-sys-plans/update-desc', {id:id, desc:content}, function (response) {
                if(response.status == 200){
                    Ewin.confirm({ message: '修改成功'}).on(function (e) {
                    });
                }
            });
        });
    });
</script>
