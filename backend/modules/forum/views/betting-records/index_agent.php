<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\BaseStringHelper;
use backend\models\SscKjData;
use backend\models\PlanAbRecord;
use common\widgets\Alert;
$newRecord = SscKjData::find()->select(['qihao','code_str'])->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->asArray()->limit(1)->one();
$planAbRecords = PlanAbRecord::findByBetRecordIds(array_map(function($model) {
    return $model->id;
}, $dataProvider->getModels()));
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BettingRecords */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Betting Records');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
?>
<section class="betting-records-index wrapper site-min-height">
    <!-- page start-->
    <?= Alert::widget() ?>
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title).'-'.$lottery_type_name.' [ '.$newRecord['qihao'].':'.$newRecord['code_str'].']'; ?>
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
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
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
                        ['attribute' => 'codes',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->codes,25);
                                return Html::a($txt, 'javascript:;', ['title' => $model->codes,'alt'=>$model->codes]);
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
                        ['attribute' => 'kj_codes','label'=>'号码','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->kj_codes ? $model->kj_codes : '待开奖';
                            }
                        ],
                        ['label' => 'A判断','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) use ($planAbRecords) {
                                $record = $planAbRecords[$model->id] ?? null;
                                if (!$record) return '';
                                return $record->a_hit ? '<font color="green">A中</font>' : '<font color="red">A不中</font>';
                            }
                        ],
                        //'position',
                        //'status',
                        //'sn',
                        //'snid',
                        //'account',
                        ['attribute'=>'snid', 'label'=>'操作',
                            'format'=>'raw',
                            'value'=>function($model){
                                $url1 = "/forum/betting-records/cancel-order?bet_id=".$model->id;
                                $url2 = "/forum/betting-records/tz-now?id=".$model->id; # 追加下注
                                $url3 = "/forum/betting-records/reverse-tz-now?id=".$model->id; # 反买
                                if($model->is_simulate){
                                    $str = '模拟';

                                    return $str.'_'.$model->sn;
                                    //$str .= ' | '.Html::a('下注', $url2, ['title' => '下注'.$model->id,'alt'=>$model->id]);
                                    //$str .= ' | '.Html::a('反买', $url3, ['title' => '反买'.$model->snid,'alt'=>$model->snid]);
                                }
                                if($model->cancel_status == 1){
                                    $str = '<font color="red">已撤单</font>';//.$model->sn;
                                    if($model->playway == 2){
                                        //$rst .= ' | '.Html::a('点击反买', $url3, ['title' => '点击反买:'.$model->snid,'alt'=>$model->snid]) ;
                                    }
                                    if(\Yii::$app->user->id == 1) $rst = '等待开奖';
                                    //return $str;
                                    return Html::a($str, '#', ['title' => '方案号：'.$model->sn,'alt'=>$model->sn]);;
                                }
                                if(!$model->status){
                                    $str = Html::a('点击撤单', $url1, ['title' => '点击撤单:'.$model->snid,'alt'=>$model->snid]);
                                    $str .= ' | '.Html::a('点击下注', $url2, ['title' => '点击下注:'.$model->id,'alt'=>$model->id]);
                                    if(\Yii::$app->user->id == 1) $str = '<font color="green">等待开奖</font>';
                                    return $str;
                                }
                                return BaseStringHelper::truncate($model->snid,15);
                            }
                        ],
                        //'playway',
                        //'playway_name',
                        ['attribute' => 'playway_name','label'=>'方式',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $str = $model->playway_name;
                                if($model->playway == 2 && $model->tz_type > 0){
                                    $buy_type_Arr = [0=>'反买', 1=>'正买'];
                                    $str .= '['.$buy_type_Arr[$model->buy_type].']';
                                }
                                if($model->tz_type > 0){
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
                        ['attribute' => 'create_time','label'=>'时间',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->create_time;
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
