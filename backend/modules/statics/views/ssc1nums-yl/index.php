<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\statics\Ssc1numsYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '一码遗漏';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc1nums-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <?php foreach ([1,2,3,4,5] as $pos){
                        $class = $pos == $position ? 'btn-success' : 'btn-default';
                    ?>
                    <div class="btn-group">
                        <?= Html::a("第{$pos}球", ['index', 'Ssc1numsYl[position]'=>$pos], ['class'=>'btn '.$class, 'style'=> 'margin-bottom:15px;']) ?>
                    </div>
                    <?php }?>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        //['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'code',
                        //'position',
                        //'today_current',
                        ['attribute'=>'today_current', 'label'=>'今出',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->today_current;
                            }
                        ],
                        'current_miss',
                        'today_miss',
                        'week_miss',
                        'month_miss',
                        //'lottery_type',
                        //'created_at',
                        //'update_time',
                        ['attribute'=>'update_time', 'label'=>'时间',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return substr($model->update_time, 11);
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
