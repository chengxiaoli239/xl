<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\Ssc3numYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc3num Yls');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
?>
<section class="ssc3num-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= $lottery_type_name.'-'.Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= $lottery_type_name.'-'.Html::a(Yii::t('app', 'Create Ssc3num Yl'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->
                <?php include(dirname(__FILE__).'/index_tab.php'); ?>

    <?php //Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'zhi',
                        ['attribute' => 'zhi','headerOptions'=>['width'=>'5%'],'label'=>'号码',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->zhi;
                                $options = [
                                    'class' => 'code_val',
                                    'data-val' => $txt,
                                    'data-lottery_type' => Yii::$app->request->queryParams['Ssc3numYl']['lottery_type'],
                                ];
                                return Html::a($txt, 'javascript:;', $options);
                            }
                        ],
                        //'current_miss',
                        ['attribute'=>'current_miss','label'=>'本期','headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return $model->current_miss;
                            }
                        ],
                        'yl_records:ntext',
                        //'last_time_miss',
                        ['attribute'=>'last_time_miss','label'=>'上次','headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return $model->last_time_miss;
                            }
                        ],
                        //'last_time_miss_range',
                        //'max_miss',
                        ['attribute'=>'max_miss','label'=>'最大','headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return $model->last_time_miss;
                            }
                        ],
                        //'max_range',
                        //'history_max_miss',
                        ['attribute'=>'history_max_miss','label'=>'历史','headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return $model->history_max_miss;
                            }
                        ],
                        //'updated_at',
                        //'created_at',
                        //'update_time',
                        ['attribute'=>'update_time','label'=>'时间','headerOptions'=>['width'=>'8%'],
                            'value'=>function($model){
                                return substr($model->update_time, 5,11);
                            }
                        ],
                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php //Pjax::end(); ?>
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
            console.log(val, lottery_type);
            $.post('/forum/ssc3num-yl/get-val-static', {val:val,lottery_type:lottery_type}, function(rst) {
                $('#tip_msg_rst').html('<strong>号码：</strong>'+val + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
                $('#rstTipModal').modal('show');
            });
        });
    })
</script>
