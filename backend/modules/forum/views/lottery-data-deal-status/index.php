<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\LotteryDataDealStatus */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Lottery Data Deal Statuses');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="lottery-data-deal-status-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Lottery Data Deal Status'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        //['attribute' => 'lottery_type',//'headerOptions'=>['width'=>'5%'],
                        //    'value' => function($model) {
                        //        return \common\service\CommonService::getLotteryName($model->lottery_type);
                        //    }
                        //],
                        //'status',
                        ['attribute'=>'status','label'=>'全局状态',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'status';
                                $url0 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->$field == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->$field){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'status_desc',
                        //'static4dPerDateProfits_status',
                        ['attribute'=>'static4dPerDateProfits_status','label'=>'A每天四定利润统计',//'headerOptions'=>['width'=>'8%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'static4dPerDateProfits_status';
                                $url0 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->$field == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->$field){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'static4dPerDateProfits_status_desc',
                        //'updateDs_status',
                        ['attribute'=>'updateDs_status','label'=>'B单双处理状态',//'headerOptions'=>['width'=>'8%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'updateDs_status';
                                $url0 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->$field == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->$field){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'updateDs_status_desc',
                        //'updateDsYL_status',
                        ['attribute'=>'updateDs_status','label'=>'C单双遗漏处理状态',//'headerOptions'=>['width'=>'8%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'updateDsYL_status';
                                $url0 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->$field == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->$field){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'updateDsYL_status_desc',
                        //'update3NumYL_status',
                        ['attribute'=>'update3NumYL_status','label'=>'D开奖三字现处理状态',//'headerOptions'=>['width'=>'8%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'update3NumYL_status';
                                $url0 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->$field == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->$field){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'update3NumYL_status_desc',
                        //'updateSdHzYL_status',
                        ['attribute'=>'updateSdHzYL_status','label'=>'E和值遗漏状态',//'headerOptions'=>['width'=>'8%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'updateSdHzYL_status';
                                $url0 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->$field == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->$field){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'updateSdHzYL_status_desc',
                        //'opProfitsPlans_status',
                        ['attribute'=>'opProfitsPlans_status','label'=>'F投注计划处理状态',//'headerOptions'=>['width'=>'8%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $field = 'opProfitsPlans_status';
                                $url0 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/lottery-data-deal-status/switch-status?field=".$field."&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->$field == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->$field){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'opProfitsPlans_status_desc',
                        //'created_at',
                        ['attribute' => 'created_at',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return date('Y-m-d H:i:s', $model->created_at);
                            }
                        ],
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
