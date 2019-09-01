<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\Ssc2numsYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc2nums Yls');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
?>
<section class="ssc2nums-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= $lottery_type_name.'-'.Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc2nums Yl'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php
                include(dirname(__FILE__).'/index_tab.php');
                ?>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'val',
                        ['attribute' => 'val','headerOptions'=>['width'=>'5%'],'label'=>'号码',
                            'value' => function($model) {
                                return $model->val;
                            }
                        ],
                        //'current_miss',
                        ['attribute' => 'current_miss','headerOptions'=>['width'=>'5%'],'label'=>'当前遗漏',
                            'value' => function($model) {
                                return $model->current_miss;
                            }
                        ],
                        //'last_time_miss',
                        ['attribute' => 'last_time_miss','headerOptions'=>['width'=>'5%'],'label'=>'上次遗漏',
                            'value' => function($model) {
                                return $model->last_time_miss;
                            }
                        ],
                        //'last_time_miss_range',
                        //'max_range',
                        'yl_records:ntext',
                        'max_miss',
                        'history_max_miss',
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
