<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\web\JsExpression;
use daixianceng\echarts\ECharts;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscDwsHzNums */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc Dws Hz Nums');
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
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Dws Hz Nums'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

    <?php Pjax::begin(); ?>
                <?php echo $this->render('_search_echarts', ['model' => $searchModel]); ?>

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
                            'title' => [
                                'text' => $positions.'位,折线图',
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
                            'grid' => [
                                'left' => '3%',
                                'right' => '4%',
                                'bottom' => '3%',
                                'containLabel' => true
                            ],
                            'toolbox' => [
                                'feature' => [
                                    'saveAsImage' => []
                                ]
                            ],
                            'xAxis' => [
                                'name' => '期号',
                                'type' => 'category',
                                'boundaryGap' => false,
                                'data' => $chartsData['xAxis']['data'],
                            ],
                            'yAxis' => [
                                'type' => 'value'
                            ],
                            'series' => $chartsData['series'],
                        ]
                    ]
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
