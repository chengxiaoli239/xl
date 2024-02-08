<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\DataDealStatus */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Data Deal Statuses');
$this->params['breadcrumbs'][] = $this->title;
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
                                $c_time = \backend\models\SscKjData::findOne(['lottery_type'=>$model->lottery_type, 'qihao'=>$model->qihao])->created_at;
                                return $model->qihao.' ['.date('H:i:s', $c_time).']';
                            }
                        ],
                        'next_qihao',
                        //'static4dPerDateProfits_status',
                        ['attribute'=>'static4dPerDateProfits_status','label'=>'A每天四定利润统计',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'static4dPerDateProfits_status';
                                $deal_desc = json_decode($model->{$field.'_desc'}, true);
                                $consume_time = ' [耗时:'.number_format($deal_desc['time_consume'], 2).'s - '.substr($deal_desc['deal_time'],11).']';
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
                            }
                        ],
                        //'static4dPerDateProfits_status_desc',
                        //'updateDs_status',
                        ['attribute'=>'static4dPerDateProfits_status','label'=>'B单双处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'updateDs_status';
                                $deal_desc = json_decode($model->{$field.'_desc'}, true);
                                $consume_time = ' [耗时:'.number_format($deal_desc['time_consume'], 2).'s - '.substr($deal_desc['deal_time'],11).']';
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
                                return $field.'_'.$model->$field;
                            }
                        ],
                        //'updateDs_status_desc',
                        //'updateDsYL_status',
                        ['attribute'=>'updateDsYL_status','label'=>'D开奖三字现处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'updateDsYL_status';
                                $deal_desc = json_decode($model->{$field.'_desc'}, true);
                                $consume_time = ' [耗时:'.number_format($deal_desc['time_consume'], 2).'s - '.substr($deal_desc['deal_time'],11).']';
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
                            }
                        ],
                        //'updateDsYL_status_desc',
                        //'update3NumYL_status',
                        ['attribute'=>'update3NumYL_status','label'=>'D开奖三字现处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'update3NumYL_status';
                                $deal_desc = json_decode($model->{$field.'_desc'}, true);
                                $consume_time = ' [耗时:'.number_format($deal_desc['time_consume'], 2).'s - '.substr($deal_desc['deal_time'],11).']';
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
                            }
                        ],
                        //'update3NumYL_status_desc',
                        //'updateSdHzYL_status',
                        ['attribute'=>'updateSdHzYL_status','label'=>'E和值遗漏状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'updateSdHzYL_status';
                                $deal_desc = json_decode($model->{$field.'_desc'}, true);
                                $consume_time = ' [耗时:'.number_format($deal_desc['time_consume'], 2).'s - '.substr($deal_desc['deal_time'],11).']';
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
                            }
                        ],
                        //'updateSdHzYL_status_desc',
                        //'opProfitsPlans_status',
                        ['attribute'=>'static4dPerDateProfits_status','label'=>'F投注计划处理状态',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'opProfitsPlans_status';
                                $deal_desc = json_decode($model->{$field.'_desc'}, true);
                                $consume_time = ' [耗时:'.number_format($deal_desc['time_consume'], 2).'s - '.substr($deal_desc['deal_time'],11).']';
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
