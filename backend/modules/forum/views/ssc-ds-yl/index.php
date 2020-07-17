<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use backend\models\SscKjData;
$newRecord = SscKjData::find()->select(['qihao','code_str'])->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->asArray()->limit(1)->one();
$newTime = \backend\models\SscKjDataDs::find()->select(['max(update_time) as update_time'])->asArray()->limit(1)->one()['update_time'];
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscDsYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc Ds Yls');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-ds-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= $lottery_type_name.'-'.Html::encode($this->title);echo '['.$newRecord['qihao'].':'.$newRecord['code_str'].']';//== '.$newTime;  ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Ds Yl'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        ['attribute'=>'positions','label'=>'位置','headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return $model->positions;
                            }
                        ],
                        //'zhi',
                        ['attribute'=>'positions','label'=>'值','headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                if(strlen($model->zhi)>4 OR in_array($model->zhi,[1111,2222])){
                                    $typeArr = [
                                        '1112,1121,1211,2111,1222,2122,2212,2221'=>'一单三双、一双三单',
                                        '1122,1212,1221,2112,2121,2211' => '两双两单',
                                        '1111,2222' => '四双四单',
                                        '1222,2122,2212,2221' => '一单三双',
                                        '2111,1211,1121,1112' => '一双三单',
                                        '2111,1211,1121,1112,1111' => '一双三单|四单',
                                        '1222,2122,2212,2221,2222' => '一单三双|四双',
                                        '1222,2122,2212,2221,1111' => '一单三双|四单',
                                        '2111,1211,1121,1112,2222' => '一双三单|四双',
                                        '1222,2122,2212,2221,1111,2222' => '一单三双|四单|四双',
                                        '2111,1211,1121,1112,2222,1111' => '一双三单|四双|四单',
                                        '1111'=>'四单',
                                        '2222'=>'四双',
                                    ];
                                    return $typeArr[$model->zhi];
                                }
                                return $model->zhi;
                            }
                        ],
                        //'current_miss',
                        ['attribute'=>'current_miss','label'=>'本期','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->current_miss;
                            }
                        ],
                        'yl_records',
                        //'last_time_miss',
                        ['attribute'=>'last_time_miss','label'=>'上次','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->last_time_miss;
                            }
                        ],
                        //'last_time_miss_range',
                        /*
                        ['attribute'=>'last_time_miss_range','label'=>'上次遗漏范围',//'headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return $model->last_time_miss_range;
                            }
                        ],
                        */
                        //'max_miss',
                        ['attribute'=>'max_miss','label'=>'最大','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->max_miss;
                            }
                        ],
                        //'max_range',
                        /*
                        ['attribute'=>'max_range','label'=>'最大遗漏范围',//'headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return $model->max_range;
                            }
                        ],
                        */
                        //'history_max_miss',
                        ['attribute'=>'history_max_miss','label'=>'历史','headerOptions'=>['width'=>'3%'],
                            'value'=>function($model){
                                return $model->history_max_miss;
                            }
                        ],
                        /*
                        ['attribute' => 'lottery_type','label'=>'彩票类型',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $txt = \common\service\CommonService::getLotteryName($model->lottery_type);

                                return $txt;
                            }
                        ],
                        */
                        //'updated_at',
                        //'update_time',
                        ['attribute'=>'update_time','label'=>'时间','headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return substr($model->update_time, 11);
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
