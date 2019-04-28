<?php
use yii\helpers\Html;
use yii\web\JsExpression;
use http\Url;
use daixianceng\echarts\ECharts;
$this->title = '定位和值(开奖前4位)';
$this->params['breadcrumbs'][] = $chartsData['positions'].'位和值';

?>
<section class="betting-records-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <header class="panel-heading">
            <?= Html::a('1,2位',['echarts','positions'=>'1,2'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('1,3位',['echarts','positions'=>'1,3'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('1,4位',['echarts','positions'=>'1,4'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('2,3位',['echarts','positions'=>'2,3'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('2,4位',['echarts','positions'=>'2,4'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('3,4位',['echarts','positions'=>'3,4'], ['class' => 'btn btn-primary']) ?>
        </header>
        <div class="panel-body">
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
                'text' => '柱状图:('.$chartsData['positions'][0].'位)',
                'subtext' => '200期出现次数',
            ],
            'tooltip' => [
                'trigger' => 'axis'
            ],
            'legend' => [
                'data' => $chartsData['positions'],
            ],
            'grid' => [
                'left' => '3%',
                'right' => '3%',
                'bottom' => '8%',
                'containLabel' => false
            ],
            'toolbox' => [
                'feature' => [
                    'saveAsImage' => []
                ]
            ],
            'xAxis' => [
                'name' => '数值',
                'type' => 'category',
                'boundaryGap' => false,
                'data' => $chartsData['xAxis'],
            ],
            'yAxis' => [
                'type' => 'value'
            ],
            'series' => $chartsData['series'],
        ]
    ]
]); ?>
    </div>
        </div>
    </section>
    <!-- page end-->
</section>
