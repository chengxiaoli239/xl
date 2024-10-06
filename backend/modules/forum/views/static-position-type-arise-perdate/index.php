<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\statics\StaticPositionTypeArisePerdate */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '位置大小单双';
$this->params['breadcrumbs'][] = $this->title;
$type1Class = $type==1 ? 'btn-success' : 'btn-default';
$type2Class = $type==2 ? 'btn-success' : 'btn-default';
$p1Name = $type==1 ? '大' : '单';
$p2Name = $type==2 ? '小' : '双';
?>
<section class="static-position-type-arise-perdate-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('大小', ['index', 'StaticPositionTypeArisePerdate[type]'=>1], ['class' => 'btn '.$type1Class, 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a('单双', ['index', 'StaticPositionTypeArisePerdate[type]'=>2], ['class' => 'btn '.$type2Class, 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'date',
                        //'type',
                        //'p1',
                        ['attribute' => 'p1_1','label'=>'千'.$p1Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p1_1;
                            }
                        ],
                        ['attribute' => 'p1_2','label'=>'千'.$p2Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p1_2;
                            }
                        ],
                        ['attribute' => 'p2_1','label'=>'百'.$p1Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p2_1;
                            }
                        ],
                        ['attribute' => 'p2_2','label'=>'百'.$p2Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p2_2;
                            }
                        ],
                        ['attribute' => 'p3_1','label'=>'十'.$p1Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p3_1;
                            }
                        ],
                        ['attribute' => 'p3_2','label'=>'十'.$p2Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p3_2;
                            }
                        ],
                        ['attribute' => 'p4_1','label'=>'个'.$p1Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p4_1;
                            }
                        ],
                        ['attribute' => 'p4_2','label'=>'个'.$p2Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p4_2;
                            }
                        ],
                        ['attribute' => 'p5_1','label'=>'五'.$p1Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p5_1;
                            }
                        ],
                        ['attribute' => 'p5_2','label'=>'五'.$p2Name,//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->p5_2;
                            }
                        ],
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',
                        ['attribute' => 'update_time','label'=>'时间',
                            'value' => function($model) {
                                return substr($model->update_time, 11);
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
