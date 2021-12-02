<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SysCustomizedFilters */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Sys Customized Filters');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="sys-customized-filters-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Sys Customized Filters'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'type',
                        ['attribute' => 'type', 'label'=>'过滤类型', 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->type;
                            }
                        ],
                        //'type_name',
                        ['attribute'=>'type_name', 'label'=>'类型名称', 'headerOptions'=>['width'=>'8%'],
                            'value' => function($model) {
                                return $model->type_name;
                            }
                        ],
                        //'playway',
                        ['attribute' => 'playway', 'label'=>'游戏类型', 'headerOptions'=>['width'=>'8%'],
                            'value' => function($model) {
                                $playway_Arr = [1=>'二字定', 2=>'三字定', 3=>'四字定', 4=>'一字定', 6=>'X字现'];

                                return $playway_Arr[$model->playway];
                            }
                        ],
                        //'status',
                        ['attribute'=>'status', 'label'=>'状态', 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url0 = "/forum/sys-customized-filters/switch-status?id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/sys-customized-filters/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                                if($model->status == 1){
                                    $txt = "<font color='green'>已开启</font>";
                                    return Html::a($txt, $url1, ['title' => '点击关闭']).'<i class="icon-refresh"></i>';
                                }
                                if(!$model->status){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']).'<i class="icon-refresh"></i>';
                                }
                            }
                        ],
                        //'codes',
                        //'sort',
                        ['attribute'=>'sort', 'label'=>'排序', 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->sort;
                            }
                        ],
                        'desc',
                        //'created_at',
                        ['attribute'=>'created_at', 'label'=>'时间', 'headerOptions'=>['width'=>'10%'],
                            'value' => function($model) {
                                return date('Y-m-d H:i:s', $model->created_at);
                            }
                        ],
                        //'updated_at',
                        //'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
