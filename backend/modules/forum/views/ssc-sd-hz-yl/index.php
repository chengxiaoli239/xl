<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscSdHzYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc Sd Hz Yls');
$this->params['breadcrumbs'][] = $this->title;
$lottery_type_name = \common\service\CommonService::getLotteryName($lottery_type);
?>
<section class="ssc-sd-hz-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title).'-'.$lottery_type_name ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Sd Hz Yl'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>
                -->

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'val',
                        ['attribute' => 'val','headerOptions'=>['width'=>'5%'],'label'=>'和值',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->val;
                                $options = [
                                    'class' => 'code_val',
                                    'data-val' => $txt,
                                    'data-lottery_type' => Yii::$app->request->queryParams['SscSdHzYl']['lottery_type'],
                                ];
                                return Html::a($txt, 'javascript:;', $options);
                            }
                        ],
                        'current_miss',
                        //'last_time_miss',
                        //'last_time_miss_range',
                        //'max_miss',
                        //'max_range',
                        'yl_records:ntext',
                        'history_max_miss',
                        'count',
                        'today_nums',
                        'theory_nums_perdate',
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
            $.post('/forum/ssc-sd-hz-yl/get-hz-static', {val:val,lottery_type:lottery_type}, function(rst) {
                $('#tip_msg_rst').html('<strong>号码：</strong>'+val + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
                $('#rstTipModal').modal('show');
            });
        });
    })
</script>
