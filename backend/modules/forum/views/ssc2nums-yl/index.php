<?php

use backend\helpers\MissHistoryFormatter;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\Ssc2numsYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc2nums Yls');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);

// 表头字段与「号码类型遗漏 - 三字复式/五字全倒」保持一致；遗漏记录只展示最新若干条，
// 完整记录放在 title 里，避免超长单元格把表格撑坏。
$missRecordDisplayLimit = 30;
?>
<section class="ssc2nums-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= $lottery_type_name.'-'.Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc2nums Yl'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php
                include(dirname(__FILE__).'/index_tab.php');
                ?>
                <?php include(dirname(__FILE__).'/../ssc-static-yl/_miss_history_assets.php'); ?>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <div class="table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn','headerOptions'=>['width'=>'3%']],

                        //'id',
                        //'val',
                        ['attribute' => 'val','label'=>'号码','headerOptions'=>['width'=>'6%'],
                            'value' => function($model) {
                                return $model->val;
                            }
                        ],
                        //'current_miss',
                        ['attribute' => 'current_miss','label'=>'当前遗漏','headerOptions'=>['width'=>'8%'],
                            'value' => function($model) {
                                return $model->current_miss;
                            }
                        ],
                        // 上次/区间最大/历史最大都是短数字，合并成一列，把宽度让给遗漏记录
                        [
                            'label'=>'上次/最大/历史',
                            'headerOptions'=>['width'=>'10%','title'=>'依次为：上次遗漏 / 区间最大遗漏 / 历史最大遗漏'],
                            'value'=>function($model){
                                return $model->last_time_miss.' / '.$model->max_miss.' / '.$model->history_max_miss;
                            }
                        ],
                        //'last_time_miss_range',
                        //'max_range',
                        //'history_max_miss',
                        //'created_at',
                        //'updated_at',
                        ['attribute' => 'update_time','label'=>'更新时间','headerOptions'=>['width'=>'10%'],
                            'value' => function($model) {
                                return substr($model->update_time, 5,11);
                            }
                        ],
                        ['attribute'=>'yl_records','label'=>'遗漏记录','format'=>'raw',
                            'value'=>function($model) use ($missRecordDisplayLimit){
                                return MissHistoryFormatter::renderListCell($model->current_miss, $model->yl_records, true, $missRecordDisplayLimit);
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
                </div>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
