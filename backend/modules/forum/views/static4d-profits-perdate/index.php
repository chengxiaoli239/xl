<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\Static4dProfitsPerdate */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static4d Profits Perdates');
$this->params['breadcrumbs'][] = $this->title;
$update_time = \backend\models\Static4dProfitsPerdate::find()->orderBy(['update_time'=>SORT_DESC])->one()->update_time;
?>
<section class="static4d-profits-perdate-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title).'[更新时间：'.$update_time.']' ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Static4d Profits Perdate'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'date',
                        //'codes_4d_all',
                        //'codes_13_31',
                        'codes_22_22',
                        'codes_1111_2222',
                        'codes_13',
                        'codes_31',
                        'codes_13_2222',
                        'codes_31_1111',
                        'codes_2222',
                        'codes_1111',
                        'codes_13_1111',
                        'codes_31_2222',
                        'codes_13_1111_2222',
                        'codes_31_2222_1111',
                        //'codes_1_nums',
                        ['attribute'=>'codes_1_nums','label'=>'单数量','headerOptions'=>['width'=>'4%'],
                            'value'=>function($model){
                                return $model->codes_1_nums;
                            }
                        ],
                        //'codes_2_nums',
                        ['attribute'=>'codes_2_nums','label'=>'双数量','headerOptions'=>['width'=>'4%'],
                            'value'=>function($model){
                                return $model->codes_2_nums;
                            }
                        ],
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
