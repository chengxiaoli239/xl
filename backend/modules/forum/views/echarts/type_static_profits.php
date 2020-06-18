<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\web\JsExpression;
use daixianceng\echarts\ECharts;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscDwsHzNums */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '计划利润统计';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-dws-hz-nums-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', '正买：981'), ['type-static-profits', 'StaticProfits[plan_id]'=>981], ['class' => 'btn '.($plan_id==981?'btn-success':'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', '反买：1029'), ['type-static-profits', 'StaticProfits[plan_id]'=>1029], ['class' => 'btn '.($plan_id==1029?'btn-success':'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

    <?php //Pjax::begin(); ?>
                <?php //echo $this->render('_search_echarts', ['model' => $searchModel]); ?>

                <?= ECharts::widget([
                    'responsive' => true,
                    'options' => [
                        'style' => 'height: 500px;'
                    ],
                    'pluginEvents' => [
                        'click' => [
                            new JsExpression('function (params) {console.log(params)}'),
                            new JsExpression('function (params) {console.log("ok")}')
                        ],
                        'legendselectchanged' => new JsExpression('function (params) {console.log(params.selected)}')
                    ],
                    'pluginOptions' => [
                        'option' => [
                            'animation' => false,
                            'grid' => [
                                'left' => '3%',
                                'right' => '4%',
                                'bottom' => '3%',
                                'containLabel' => true
                            ],
                            /*
                            'title' => [
                                'text' => $positions.'计划折线图',
                                'subtext'=> '区间动态统计图'
                            ],
                            'tooltip' => [
                                'trigger' => 'axis',
                                'axisPointer'=> [
                                    'type' => 'cross',
                                ],
                            ],
                            'legend' => [
                                'data' => $periods,
                            ],
                            'toolbox' => [
                                'feature' => [
                                    'saveAsImage' => []
                                ]
                            ],
                            */
                            'xAxis' => [
                                'name' => '期号',
                                'minorTick' => [
                                    'show'=> true
                                ],
                                'splitLine'=> [
                                    'lineStyle' => [
                                        'color'=> '#999'
                                    ]
                                ],
                                'minorSplitLine' => [
                                    'show' => true,
                                    'lineStyle' => [
                                        'color' => '#ddd'
                                    ]
                                ]
                                /*
                                'type' => 'category',
                                'boundaryGap' => false,
                                'data' => $chartsData['xAxis']['data'],
                                */
                            ],
                            'yAxis' => [
                                'name' => '利润',
                                //'type' => 'value',
                                'min' => 0-$chartsData['range'],
                                'max' => $chartsData['range'],
                                'minorTick' => [
                                    'show'=> true
                                ],
                                'splitLine'=> [
                                    'lineStyle' => [
                                        'color'=> '#999'
                                    ]
                                ],
                                'minorSplitLine' => [
                                    'show' => true,
                                    'lineStyle' => [
                                        'color' => '#ddd'
                                    ]
                                ]
                            ],
                           'dataZoom' => [
                               [
                                   'show' => true,
                                   'type' => 'inside',
                                   'filterMode' => 'none',
                                   'xAxisIndex' => [0],
                                   'startValue' => 0,
                                   'endValue' =>$chartsData['range'],
                               ],
                               [
                                   'show' => true,
                                   'type' => 'inside',
                                   'filterMode' => 'none',
                                   'yAxisIndex' => [0],
                                   'startValue' => 0-$chartsData['range'],
                                    'endValue' => $chartsData['range'],
                               ]
                           ],
                           'series' => $chartsData['series'],
                            /*
                           'series' => [
                               [
                                   'name'=>'利润',
                                   'type' => 'line',
                                   //'showSymbol' => false,
                                   'smooth' => true,
                                   //'clip' => true,
                                   'data'=>[[0, 30], [10, -20], [20, 30], [30, 40], [50,-50], [60, 80], [70, -180]]
                               ]
                           ],
                            */
                        ]
                    ]
                ]); ?>
    <?php //Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<?php $this->endBody() ?>
