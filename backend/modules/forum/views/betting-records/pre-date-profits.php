<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\BaseStringHelper;
use backend\models\SscKjData;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BettingRecords */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Pre Date Profits');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="betting-records-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Betting Records', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

    <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'playway',
                        ['attribute' => 'playway', 'label'=>'投注方式', # 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return backend\service\BetService::lotteryClass($model->playway);
                            }
                        ],
                        ['attribute' => 'tz_num', 'label'=>'投注期数',# 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return (int)round((float)$model->tz_num, 0);
                            }
                        ],
                        ['attribute' => 'tz_money', 'label'=>'投注金额',# 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->tz_money;
                            }
                        ],
                        ['attribute' => 'profits', 'label'=>'利润',# 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->profits;
                            }
                        ],
                        ['attribute' => 'zj_money', 'label'=>'中奖金额',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->zj_money;
                            }
                        ],
                        ['attribute' => 'tz_date', 'label'=>'投注日期',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->tz_date;
                            }
                        ],
                        //'update_time',
                        ['attribute' => 'tz_date', 'label'=>'最新时间',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->update_time;
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
