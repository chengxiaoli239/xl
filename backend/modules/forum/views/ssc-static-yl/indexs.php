<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscStaticYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

//p($codeTypeName);
$this->title = Yii::t('app', 'Ssc Static Yls'); # .' [ '.$codeTypeName.' ]';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-static-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Ssc Static Yl', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/code_type_tab.php'); ?>

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
                                return \backend\service\SscDataService::getStaticNameByType($model->val);
                            }
                        ],
                        //'current_miss',
                        ['attribute' => 'current_miss','headerOptions'=>['width'=>'5%'],'label'=>'当前遗漏',
                            'value' => function($model) {
                                return $model->current_miss;
                            }
                        ],
                        //'last_time_miss',
                        ['attribute' => 'last_time_miss','headerOptions'=>['width'=>'5%'],'label'=>'当前遗漏',
                            'value' => function($model) {
                                return $model->last_time_miss;
                            }
                        ],
                        //'last_time_miss_range',
                        //'max_miss',
                        ['attribute' => 'max_miss','headerOptions'=>['width'=>'5%'],'label'=>'近期最大',
                            'value' => function($model) {
                                return $model->max_miss;
                            }
                        ],
                        //'max_range',
                        'yl_records:ntext',
                        //'history_max_miss',
                        ['attribute' => 'history_max_miss','headerOptions'=>['width'=>'5%'],'label'=>'历史最大',
                            'value' => function($model) {
                                return $model->history_max_miss;
                            }
                        ],
                        //'codes_hz',
                        ['attribute' => 'codes_hz','headerOptions'=>['width'=>'5%'],'label'=>'和值',
                            'value' => function($model) {
                                return $model->codes_hz;
                            }
                        ],
                        //'type_3b',
                        ['attribute' => 'type_3b','headerOptions'=>['width'=>'5%'],'label'=>'三兄弟',
                            'value' => function($model) {
                                return $model->type_3b;
                            }
                        ],
                        //'type_3',
                        ['attribute' => 'type_3','headerOptions'=>['width'=>'5%'],'label'=>'三重',
                            'value' => function($model) {
                                return $model->type_3;
                            }
                        ],
                        //'type_4',
                        ['attribute' => 'type_4','headerOptions'=>['width'=>'5%'],'label'=>'四重',
                            'value' => function($model) {
                                return $model->type_4;
                            }
                        ],
                        //'type_22',
                        ['attribute' => 'type_22','headerOptions'=>['width'=>'5%'],'label'=>'双双重',
                            'value' => function($model) {
                                return $model->type_22;
                            }
                        ],
                        //'type_4ds',
                        ['attribute' => 'type_4ds','headerOptions'=>['width'=>'5%'],'label'=>'四单双',
                            'value' => function($model) {
                                return $model->type_4ds;
                            }
                        ],
                        //'count',
                        ['attribute' => 'count','headerOptions'=>['width'=>'5%'],'label'=>'总组数',
                            'value' => function($model) {
                                return $model->count;
                            }
                        ],
                        //'static_nums',
                        //'theory_nums_perdate',
                        //'today_nums',
                        //'ytd_nums',
                        //'lottery_type',
                        //'status',
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
