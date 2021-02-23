<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscDwHzStatic */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc Dw Hz Statics');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-dw-hz-static-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <header class="panel-heading">
            <?= Html::a('20期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=20', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('50期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=50', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('100期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=100', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('200期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=200', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('300期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=300', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('500期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=500', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('1000期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=1000', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('2000期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=2000', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('5000期','/forum/ssc-dw-hz-static/index?SscDwHzStatic[periods]=5000', ['class' => 'btn btn-primary']) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Dw Hz Static'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

    <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        ['attribute'=>'positions','label'=>'位置','value'=>function($model){
                            return $model->positions;
                        }],
                        //'qihao',
                        ['attribute'=>'periods','label'=>'区间','value'=>function($model){
                            return $model->periods;
                        }],
                        //'hz_0',
                        //'hz_1',
                        //'hz_2',
                        //'hz_3',
                        //'hz_4',
                        //'hz_5',
                        'hz_6',
                        'hz_7',
                        'hz_8',
                        'hz_9',
                        'hz_10',
                        'hz_11',
                        'hz_12',
                        'hz_13',
                        //'hz_14',
                        //'hz_15',
                        //'hz_16',
                        //'hz_17',
                        //'hz_18',
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn','headerOptions' => ['width' => '5%'],],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
