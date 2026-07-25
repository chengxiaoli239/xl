<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\BaseStringHelper;
use backend\models\SscKjData;
use backend\models\PlanAbRecord;
use common\widgets\Alert;
$newRecord = SscKjData::find()->select(['qihao','code_str'])->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->asArray()->limit(1)->one();
$newRecordText = $newRecord ? '['.$newRecord['qihao'].':'.$newRecord['code_str'].']' : '';
$planAbRecords = PlanAbRecord::findByBetRecordIds(array_map(function($model) {
    return $model->id;
}, $dataProvider->getModels()));
$type13PlanIds = array_flip(\backend\models\UserSysPlans::find()
    ->select('id')
    ->where([
        'id' => array_map(function($model) { return $model->plan_id; }, $dataProvider->getModels()),
        'plan_type' => 13,
    ])
    ->column());
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BettingRecords */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Betting Records');
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
    /* 默认的弹框大小 */
    .modal-lg {
        width: 85%;
        height: 30%;
        margin: 100px auto;
    }

    /* 在小屏幕上设置较大的弹框大小 */
    @media (max-width: 768px) {
        .modal-lg {
            width: 98%;
            height: 30%;
            margin: 50px auto;
        }
    }
</style>
<section class="betting-records-index wrapper site-min-height">
    <!-- page start-->
    <?= Alert::widget() ?>
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title);echo $newRecordText; ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Betting Records', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php Pjax::begin(); ?>

                <?php if(\Yii::$app->user->id == 1) echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'pager' => [
                        'firstPageLabel' => '首页',
                        'lastPageLabel' => '尾页',
                    ],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'qihao',
                        ['attribute' => 'qihao','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->qihao;
                            }
                        ],
                        ['attribute' => 'codes','headerOptions'=>['width'=>'20%'],
                            'format'=>'raw',
                            'value' => function($model) use ($planAbRecords, $type13PlanIds) {
                                $txt = BaseStringHelper::truncate($model->codes,15);
                                $content = Html::a($txt, 'javascript:;', [
                                    'class'=>'act-post-desc',
                                    'title'=>$model->post_desc,
                                    'alt'=>str_replace('@', ',',str_replace(',', '',$model->codes)),
                                ]);
                                if(isset($type13PlanIds[$model->plan_id])){
                                    $record = $planAbRecords[$model->id] ?? null;
                                    if($record){
                                        $result = $record->a_hit
                                            ? '<font color="green">A中</font>'
                                            : '<font color="red">A不中</font>';
                                        $content .= '<br>A判断：'.$result;
                                    }
                                }
                                return $content;
                            }
                        ],
                        //'betting_money',
                        ['attribute' => 'betting_money','label'=>'投注','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->betting_money;
                            }
                        ],
                        //'bonus',
                        ['attribute' => 'bonus','label'=>'中奖','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->bonus>0 ? '<font color="green">'.$model->bonus.'</font>' : ' ';
                            }
                        ],
                        //'single',
                        ['attribute' => 'single','label'=>'倍',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->single;
                            }
                        ],
                        'profits',
                        /*
                        ['attribute' => 'profits','headerOptions' => ['width' => '5%'],
                            'value' => function($model) {
                                return $model->profits;
                            }
                        ],
                        */
                        //'kj_codes',
                        ['attribute' => 'kj_codes', 'label'=>'号码','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->kj_codes ? $model->kj_codes : '待开奖';
                            }
                        ],
                        //'position',
                        //'status',
                        //'sn',
                        //'snid',
                        //'account',
                        ['attribute' => 'account','label'=>'账号','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::a($model->account, '/forum/betting-records/index?BettingRecords[account]='.$model->account);
                            }
                        ],
                        //'plan_id',
                        ['attribute' => 'plan_id','label'=>'planid','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $plan = \backend\models\UserSysPlans::findOne($model->plan_id);
                                $alt_str = \backend\service\NumService::getDescByKuaixuan(json_decode($plan->hz_Arr, true));
                                if($plan->singles && in_array($plan->plan_type,[2, 3, 4, 5, 9, 10])){
                                    $alt_str .= '翻倍梯度:'.$plan->singles;
                                }
                                $options = [
                                    'title' => ($model->buy_type==1?'x':'【反买】').\backend\service\TzService::getTzPlanTypes($plan->plan_type) . '，'.$alt_str,
                                ];
                                return Html::a($model->plan_id, '/forum/betting-records/index?BettingRecords[plan_id]='.$model->plan_id, $options);
                            }
                        ],
                        ['attribute'=>'snid', 'label'=>'操作',
                            'format'=>'raw',
                            'value'=>function($model){
                                $url1 = "/forum/betting-records/cancel-order?bet_id=".$model->id;
                                $url2 = "/forum/betting-records/tz-now?id=".$model->id; # 追加下注
                                $url3 = "/forum/betting-records/reverse-tz-now?id=".$model->id; # 反买
                                if($model->is_simulate){
                                    $str = '模拟'.$model->plan_id.'_'.$model->sn;
                                    //$str .= ' | '.Html::a('下注', $url2, ['title' => '下注'.$model->id,'alt'=>$model->id]);
                                    //$str .= ' | '.Html::a('反买', $url3, ['title' => '反买'.$model->snid,'alt'=>$model->snid]);
                                    return $str;
                                }
                                if($model->cancel_status == 1){
                                    $rst = '<font color="red">已撤单</font>';
                                    if($model->playway == 2){
                                        //$rst .= ' | '.Html::a('点击反买', $url3, ['title' => '点击反买:'.$model->snid,'alt'=>$model->snid]) ;
                                    }
                                    if(\Yii::$app->user->id == 1) $rst = '已撤单';
                                    return $rst;
                                }
                                if(!$model->status){
                                    $str = Html::a('点击撤单', $url1, ['title' => '点击撤单:'.$model->snid,'alt'=>$model->snid]);
                                    $str .= ' | '.Html::a('点击下注', $url2, ['title' => '点击下注:'.$model->id,'alt'=>$model->id]);
                                    if(\Yii::$app->user->id == 1) $str = Html::a('<font color="green">等待开奖</font>', '#', ['title' => '方案号:'.$model->snid,'alt'=>$model->snid]);
                                    return $str;
                                }
                                $str = Html::a(BaseStringHelper::truncate($model->snid,18), 'javascript:;', ['title' => '方案号：'.$model->snid,'alt'=>$model->snid]);
                                return $str;
                            }
                        ],
                        //'playway',
                        //'playway_name',
                        ['attribute' => 'playway_name','label'=>'方式',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $str = $model->playway_name;
                                if($model->playway == 2 && $model->tz_type > 0){
                                    $buy_type_Arr = [0=>'反买', 1=>'正买'];
                                    $str .= '['.$buy_type_Arr[$model->buy_type].']';
                                }
                                if($model->playway == 3 && $model->tz_type > 0){
                                    //$str .= '['.\backend\service\StaticService::$kArr[$model->tz_type].']';
                                    $str .= '['.\backend\service\BetService::getTypeNameByTzType($model->tz_type).']';
                                }
                                return $str;
                            }
                        ],
                        //'is_simulate',
                        //'lotteryclass',
                        //'createtime:datetime',
                        //'create_time',
                        ['attribute' => 'create_time','label'=>'时间','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return substr($model->create_time, 10);
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn','headerOptions' => ['width' => '5%'],'template'=>'{view}  {delete}'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<!--提示框-start-->
<div class="modal fade " id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" >
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>推送描述：</strong>
                    <pre><code id="push_content"></code></pre>
                    <strong>推送号码：</strong>
                    <pre><code id="rst_code"></code></pre>
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
<!--提示框-end-->
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
    $(function () {
        //$("[id^='act-post-desc']").click(function (rst) {
        $(".act-post-desc").click(function (rst) {
            bet_rst = $(this).attr('alt');
            content = $(this).attr('title');

            $('#rst_code').text(bet_rst)
            //$('#push_content').text("<pre><code>"+content+"</code></pre>")
            // 解析并格式化 JSON 字符串
            try {
                var jsonObj = JSON.parse(content);
                var formattedJson = JSON.stringify(jsonObj, null, 4); // 2 spaces for indentation
                $('#push_content').html("<pre><code>" + formattedJson + "</code></pre>");
            } catch (e) {
                // Handle the case where content is not a valid JSON string
                $('#push_content').text("Invalid JSON string");
            }

            $('#exampleModal_msg').modal('show');
        });
    });
</script>
