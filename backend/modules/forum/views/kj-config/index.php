<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\KjConfig */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Kj Configs');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="kj-config-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title);?>
            <div class="btn-group">
                <?= Html::a(Yii::t('app', 'Create Kj Config'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
            </div>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <?php foreach ($lottery_types as $lottery_type): ?>
                    <div class="btn-group">
                        <?= Html::a($lottery_type['name'], ['index', 'KjConfig[lottery_type]'=>$lottery_type['lottery_type']], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <?php endforeach;?>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'title',
                        //'name',
                        ['attribute' => 'lottery_type','label'=>'彩种类型',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $str = \common\kj\cqssc\CqsscKcw::$lotteryNameArr[$model->lottery_type];
                                return $str;
                            }
                        ],
                        'host',
                        'api_host',
                        'path',
                        //'is_batch',
                        ['attribute' => 'is_batch','label'=>'是否批量',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $str = $model->is_batch ? '是' : '否';
                                return $str;
                            }
                        ],
                        //'method',
                        //'post_data',
                        //'data_type',
                        //'enable',
                        ['attribute'=>'enable', 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/kj-config/switch-status?id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/kj-config/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                                if($model->enable == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->enable){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                //return $model->snid;
                            }
                        ],
                        //'lottery_type',

                        //'created_at',
                        'updated_at',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
