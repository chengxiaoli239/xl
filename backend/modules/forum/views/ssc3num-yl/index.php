<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\Ssc3numYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc3num Yls');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
?>
<section class="ssc3num-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= $lottery_type_name.'-'.Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= $lottery_type_name.'-'.Html::a(Yii::t('app', 'Create Ssc3num Yl'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->
                <?php include(dirname(__FILE__).'/index_tab.php'); ?>

    <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'zhi',
                        ['attribute'=>'zhi','label'=>'值','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->zhi;
                            }
                        ],
                        //'current_miss',
                        ['attribute'=>'current_miss','label'=>'本期遗漏','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->current_miss;
                            }
                        ],
                        'yl_records:ntext',
                        //'last_time_miss',
                        ['attribute'=>'last_time_miss','label'=>'上次遗漏','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->last_time_miss;
                            }
                        ],
                        'last_time_miss_range',
                        'max_miss',
                        'max_range',
                        //'history_max_miss',
                        ['attribute'=>'history_max_miss','label'=>'历史最大','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->history_max_miss;
                            }
                        ],
                        //'updated_at',
                        //'created_at',
                        'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
