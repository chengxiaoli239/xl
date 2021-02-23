<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\StaticPeiShuCodeDateProfits */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static Pei Shu Code Date Profits');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static-pei-shu-code-date-profits-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Static Pei Shu Code Date Profits', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>
                -->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
                <?php include(dirname(__FILE__).'/index_tab.php'); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'date',
                        ['attribute' => 'date','label'=>'日期',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->date;
                            }
                        ],
                        'code_147_369',
                        'code_258_369',
                        'code_019_368',
                        'code_123_678',
                        'code_147_258',
                        'code_017_348',
                        'code_456_789',
                        'code_012_789',
                        'code_345_678',
                        'code_357_019',
                        //'code_3b',
                        ['attribute' => 'code_3b','label'=>'三兄',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->code_3b;
                            }
                        ],
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        ['attribute' => 'update_time','label'=>'时间',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return substr($model->update_time, 10,9);
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
