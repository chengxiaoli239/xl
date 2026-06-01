<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\DataDealStatus */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Data Deal Statuses');
$this->params['breadcrumbs'][] = $this->title;

$renderDealStatus = function($model, $field) {
    $deal_desc = json_decode($model->{$field.'_desc'}, true);
    $consume_time = '';
    if (is_array($deal_desc)) {
        $timeConsume = $deal_desc['time_consume'] ?? null;
        $dealTime = $deal_desc['deal_time'] ?? '';
        $timeText = is_numeric($timeConsume) ? number_format((float)$timeConsume, 2) : '-';
        $dealTimeText = is_scalar($dealTime) ? substr((string)$dealTime, 11) : '';
        $consume_time = ' [耗时:'.$timeText.'s'.($dealTimeText !== '' ? ' - '.$dealTimeText : '').']';
    }

    if($model->$field == 2){
        $txt = "<font color='green'>√</font>".$consume_time;
        return Html::a($txt, 'javascript:;', ['title' => '已完成']);
    }
    if($model->$field == 0){
        $txt = "<font color='gray'>--</font>";
        return Html::a($txt, 'javascript:;', ['title' => '待处理']);
    }
    if($model->$field == 3){
        $txt = "<font color='red'>X</font>".$consume_time;
        return Html::a($txt, 'javascript:;', ['title' => '未完成']);
    }
    if($model->$field == 4){
        $txt = "<font color='#cd5c5c'>未开启统计</font>";
        return Html::a($txt, 'javascript:;', ['title' => '处理开关未开启']);
    }

    return Html::a(Html::encode($field.'_'.$model->$field), 'javascript:;', ['title' => '未知状态']);
};
?>
<section class="data-deal-status-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Data Deal Status'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'lottery_type',
                        //'status',
                        ['attribute'=>'status','label'=>'全局状态',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'status';
                                if($model->$field == 2){
                                    $txt = "<font color='green'>√ 已完成</font>" ;
                                    return Html::a($txt, 'javascript:;', ['title' => '已完成']);
                                }

                                if($model->$field == 0){
                                    $txt = "<font color='gray'>未完成所有</font>";
                                    return Html::a($txt, 'javascript:;', ['title' => '未完成处理']);
                                }
                                if($model->$field == 3){
                                    $txt = "<font color='red'>处理失败</font>";
                                    return Html::a($txt, 'javascript:;', ['title' => '未完成处理']);
                                }
                                if($model->$field == 4){
                                    $txt = "<font color='#cd5c5c'>未开启统计</font>";
                                    return Html::a($txt, 'javascript:;', ['title' => '处理开关未开启']);
                                }
                            }
                        ],
                        //'status_desc',
                        //'qihao',
                        ['attribute'=>'qihao','label'=>'期号',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $kjData = \backend\models\SscKjData::findOne(['lottery_type'=>$model->lottery_type, 'qihao'=>$model->qihao]);
                                $c_time = !empty($kjData) ? (int)$kjData->created_at : 0;
                                return $model->qihao.($c_time > 0 ? ' ['.date('H:i:s', $c_time).']' : '');
                            }
                        ],
                        'next_qihao',
                        //'static4dPerDateProfits_status',
                        ['attribute'=>'static4dPerDateProfits_status','label'=>'A每天四定利润统计',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model) use ($renderDealStatus) {
                                return $renderDealStatus($model, 'static4dPerDateProfits_status');
                            }
                        ],
                        //'static4dPerDateProfits_status_desc',
                        //'updateDs_status',
                        ['attribute'=>'updateDs_status','label'=>'B单双处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model) use ($renderDealStatus) {
                                return $renderDealStatus($model, 'updateDs_status');
                            }
                        ],
                        //'updateDs_status_desc',
                        //'updateDsYL_status',
                        ['attribute'=>'updateDsYL_status','label'=>'D开奖三字现处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model) use ($renderDealStatus) {
                                return $renderDealStatus($model, 'updateDsYL_status');
                            }
                        ],
                        //'updateDsYL_status_desc',
                        //'update3NumYL_status',
                        ['attribute'=>'update3NumYL_status','label'=>'D开奖三字现处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model) use ($renderDealStatus) {
                                return $renderDealStatus($model, 'update3NumYL_status');
                            }
                        ],
                        //'update3NumYL_status_desc',
                        //'updateSdHzYL_status',
                        ['attribute'=>'updateSdHzYL_status','label'=>'E和值遗漏状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model) use ($renderDealStatus) {
                                return $renderDealStatus($model, 'updateSdHzYL_status');
                            }
                        ],
                        //'updateSdHzYL_status_desc',
                        //'opProfitsPlans_status',
                        ['attribute'=>'opProfitsPlans_status','label'=>'F投注计划处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model) use ($renderDealStatus) {
                                return $renderDealStatus($model, 'opProfitsPlans_status');
                            }
                        ],
                        //'opProfitsPlans_status_desc',
                        //'created_at',
                        //'updated_at',
                        'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
