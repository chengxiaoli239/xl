<?php

use backend\service\SscDataService;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscStaticYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

//p($codeTypeName);
$this->title = Yii::t('app', 'Ssc Static Yls'); # .' [ '.$codeTypeName.' ]';
$this->params['breadcrumbs'][] = $this->title;

$qiShu = SscDataService::getQiShu($lottery_type);
$hasOpenQiShu = \backend\models\SscKjData::find()->where(['lottery_type'=>$lottery_type, 'date'=>date('Y-m-d')])->count();
?>
<section class="ssc-static-yl-index wrapper site-min-height">
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
                        <?= Html::a('Create Ssc Static Yl', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/code_type_tab.php'); ?>
                <?php include(dirname(__FILE__).'/_miss_history_assets.php'); ?>


                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <div class="table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn','headerOptions'=>['width'=>'3%']],

                        //'id',
                        //'val',
                        ['attribute' => 'val','headerOptions'=>['width'=>'6%'],'label'=>'号码',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = \backend\service\SscDataService::getStaticNameByType($model->val);
                                $options = [
                                    'class' => 'code_val',
                                    'data-val' => $model->val,
                                    'data-lottery_type' => Yii::$app->request->queryParams['SscStaticYl']['lottery_type'],
                                ];
                                return Html::a($txt, 'javascript:;', $options);
                            }
                        ],
                        ['attribute' => 'current_miss','headerOptions'=>['width'=>'6%'],'label'=>'当前遗漏',
                            'value' => function($model) {
                                return $model->current_miss;
                            }
                        ],
                        // 上次/最大/历史都是短数字，合并成一列，把宽度让给遗漏记录
                        [
                            'label'=>'上次/最大/历史',
                            'headerOptions'=>['width'=>'10%','title'=>'依次为：上次遗漏 / 区间最大遗漏 / 历史最大遗漏'],
                            'value'=>function($model){
                                return $model->last_time_miss.' / '.$model->max_miss.' / '.$model->history_max_miss;
                            }
                        ],
                        //'last_time_miss',
                        //'last_time_miss_range',
                        //'max_miss',
                        //'max_range',
                        //'history_max_miss',
                        ['attribute' => 'count','headerOptions'=>['width'=>'5%'],'label'=>'组数',
                            'value' => function($model) {
                                return $model->count;
                            }
                        ],
                        //'static_nums',
                        ['attribute' => 'theory_nums_perdate','headerOptions'=>['width'=>'7%'],'label'=>'理论次/天',
                            'value' => function($model) {
                                return $model->theory_nums_perdate;
                            }
                        ],
                        ['attribute' => 'today_nums','headerOptions'=>['width'=>'5%'],'label'=>'今出(次)',
                            'value' => function($model) {
                                return $model->today_nums;
                            }
                        ],
                        ['attribute' => 'ytd_nums','headerOptions'=>['width'=>'5%'],'label'=>'昨出(次)',
                            'value' => function($model) {
                                return $model->ytd_nums;
                            }
                        ],
                        //'lottery_type',
                        //'status',
                        //'created_at',
                        //'updated_at',
                        ['attribute' => 'update_time','headerOptions'=>['width'=>'7%'],'label'=>'更新时间',
                            'format'=>'raw',
                            'value' => function($model) {
                                return substr($model->update_time, 10, 9);
                            }
                        ],
                        ['attribute'=>'yl_records','label'=>'遗漏记录','format'=>'raw',# 'yl_records:ntext',
                            'value'=>function($model){
                                // 本页各号码类型的 yl_records 不一定含当前遗漏，保持原样不去重
                                return \backend\helpers\MissHistoryFormatter::renderListCell($model->current_miss, $model->yl_records, false);
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
<div class="modal fade" id="rstTipModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">提示信息</h4>
            </div>
            <div class="modal-body">
                <div class="form-group up-reason">
                    <label id="tip_msg_rst" for="tip_msg_rst"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="opRstConfirm">确定</button>
            </div>
        </div>
    </div>
</div>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
    $(function () {
        $('.code_val').click(function () {
            val = $(this).data('val');
            lottery_type = $(this).data('lottery_type');
            $.post('/forum/ssc-static-yl/get-code-type-static', {val:val,lottery_type:lottery_type}, function(rst) {
                $('#tip_msg_rst').html('<strong>号码：</strong>'+rst.val_desc + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录（最新 → 更早）：</strong>" + renderMissHistory(rst.current_times, rst.yl_str))
                $('#rstTipModal').modal('show');
            });
        });
    })
</script>
