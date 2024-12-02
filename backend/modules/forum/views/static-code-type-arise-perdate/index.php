<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\StaticCodeTypeArisePerdate */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static Code Type Arise Perdates');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
?>
<section class="static-code-type-arise-perdate-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title).'-'.$lottery_type_name ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Static Code Type Arise Perdate', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        ['attribute' => 'date','headerOptions'=>['width'=>'5%'], 'label'=>'日期',
                            'value' => function($model) {
                                return substr($model->date, 5,10);
                            }
                        ],
                        'type_2',
                        'type_3',
                        'type_22',
                        'type_2b',
                        'type_3b',
                        'type_4b',
                        //'type_2_type_2b',
                        'type_2_type_3b',
                        'type_3n_2b',
                        'type_22b',
                        'type_log',
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',
                        ['attribute' => 'update_time','headerOptions'=>['width'=>'5%'], 'label'=>'时间',
                            'value' => function($model) {
                                return substr($model->update_time, 11,5);
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
