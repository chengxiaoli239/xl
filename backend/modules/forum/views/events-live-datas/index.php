<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\EventsLiveDatas */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Events Live Datas');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="events-live-datas-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Events Live Datas'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'uid',
                        //'event_id',
                        ['attribute' => 'event_id','label'=>'项目ID','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->event_id;
                            }
                        ],
                        #'clock_period',

                        # 时间
                        //'clock_minute',
                        //'clock_second',
                        #'clock_minutesLeftInPeriod',
                        #'clock_secondsLeftInMinute',
                        ['attribute' => 'clock_period','label'=>'比赛时间',//'headerOptions'=>['width'=>'15%'],
                            'value' => function($model) {
                                return '第'.$model->clock_period.'节 '.$model->clock_minute.'\''.trim($model->clock_second).'" '.
                                    '剩余：'.$model->clock_minutesLeftInPeriod.'\''.$model->clock_secondsLeftInMinute.'"';
                            }
                        ],

                        //'clock_running',
                        #['attribute' => 'clock_running','label'=>'进行中',//'headerOptions'=>['width'=>'15%'],
                        #    'value' => function($model) {
                        #        return $model->clock_running ? '是' : '否';
                        #    }
                        #],

                        ['attribute' => 'group_name','label'=>'联赛',//'headerOptions'=>['width'=>'12%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return '<strong><font color="#006400">['.$model->score_home.'-'.$model->score_away.']</font></strong> '.$model->group_name;
                            }
                        ],
                        //'home_name_en',
                        ['attribute' => 'home_name_en','label'=>'主队',//'headerOptions'=>['width'=>'15%'],
                            'value' => function($model) {
                                return '主：'.$model->home_name_en;
                            }
                        ],
                        //'way_name_en',
                        ['attribute' => 'way_name_en','label'=>'客队',//'headerOptions'=>['width'=>'15%'],
                            'value' => function($model) {
                                return '客：'.$model->way_name_en;
                            }
                        ],
                        #'score_home',
                        #'score_away',

                        //'score_info',
                        //'score_who',

                        # 黄牌
                        //'statics_football_home_yellowCards',
                        //'statics_football_way_yellowCards',
                        ['attribute' => 'statics_football_home_yellowCards','label'=>'黄牌','headerOptions'=>['width'=>'8%'],
                            'value' => function($model) {
                                return '主 '.(int)$model->statics_football_home_yellowCards."-".(int)$model->statics_football_way_yellowCards.' 客';
                            }
                        ],

                        # 红牌
                        #'statics_football_home_redCards',
                        #'statics_football_way_redCards',
                        ['attribute' => 'statics_football_home_redCards','label'=>'红牌','headerOptions'=>['width'=>'8%'],
                            'value' => function($model) {
                                return '主 '.(int)$model->statics_football_home_redCards."-".(int)$model->statics_football_way_redCards.' 客';
                            }
                        ],

                        # 角球
                        #'statics_football_home_corners',
                        #'statics_football_way_corners',
                        ['attribute' => 'statics_football_home_corners','label'=>'角球','headerOptions'=>['width'=>'8%'],
                            'value' => function($model) {
                                return '主 '.(int)$model->statics_football_home_corners."-".(int)$model->statics_football_way_corners.' 客';
                            }
                        ],

                        //'liveStatistics:ntext',
                        //'created_at',
                        //'updated_at',
                        //'update_time',
                        ['attribute' => 'update_time','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return substr($model->update_time, 5);
                            }
                        ],

                        #['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
