<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\StaticHzArisePerdate */
/* @var $dataProvider yii\data\ActiveDataProvider */

//$this->title = 'Static Hz Arise Perdates';
$this->title = Yii::t('app', 'Static Hz Arise Perdates');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
?>
<section class="static-hz-arise-perdate-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
            <?= Html::encode($this->title) . '，总期数:<font color="#663399">'.$qiShu.'</font>期，已开:<font color="green">'.$hasOpenQiShu . '</font>期，待开:<font color="red">'.($qiShu-$hasOpenQiShu).'</font>期'; ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Static Hz Arise Perdate', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'date',
                        'hz_0_6',
                        'hz_5_10',
                        'hz_11_15',
                        'hz_16_19',
                        'hz_20_24',
                        'hz_25_29',
                        'hz_30_36',
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',
                        ['attribute' => 'update_time','headerOptions'=>['width'=>'3%'],'label'=>'时间',
                            'value' => function($model) {
                                return substr($model->update_time, 10, 9);
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
