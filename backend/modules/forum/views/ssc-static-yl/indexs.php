<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscStaticYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

//p($codeTypeName);
$this->title = Yii::t('app', 'Ssc Static Yls').' [ '.$codeTypeName.' ]';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-static-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Ssc Static Yl', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php
                include(dirname(__FILE__).'/code_type_tab.php');
                ?>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'val',
                        ['attribute' => 'val','headerOptions'=>['width'=>'5%'],'label'=>'号码',
                            'value' => function($model) {
                                return \backend\service\SscDataService::getStaticNameByType($model->val);
                            }
                        ],
                        'current_miss',
                        'last_time_miss',
                        //'last_time_miss_range',
                        'max_miss',
                        //'max_range',
                        'yl_records:ntext',
                        //'history_max_miss',
                        'count',
                        //'static_nums',
                        //'theory_nums_perdate',
                        //'today_nums',
                        //'ytd_nums',
                        //'lottery_type',
                        //'status',
                        //'created_at',
                        //'updated_at',
                        'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
