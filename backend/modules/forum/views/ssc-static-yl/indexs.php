<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscStaticYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

//p($codeTypeName);
$this->title = Yii::t('app', 'Ssc Static Yls'); # .' [ '.$codeTypeName.' ]';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-static-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Ssc Static Yl', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/code_type_tab.php'); ?>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'val',
                        ['attribute' => 'val','headerOptions'=>['width'=>'5%'],'label'=>'号码',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = \backend\service\SscDataService::getStaticNameByType($model->val);
                                $options = [
                                    'class' => 'code_val',
                                    'data-val' => $txt,
                                    'data-type' => Yii::$app->request->queryParams['SscStaticYl']['type'],
                                    'data-lottery_type' => Yii::$app->request->queryParams['SscStaticYl']['lottery_type'],
                                ];
                                return Html::a($txt, '#', $options);
                            }
                        ],
                        //'current_miss',
                        ['attribute' => 'current_miss','headerOptions'=>['width'=>'5%'],'label'=>'当前',
                            'value' => function($model) {
                                return $model->current_miss;
                            }
                        ],
                        //'last_time_miss',
                        ['attribute' => 'last_time_miss','headerOptions'=>['width'=>'5%'],'label'=>'上次',
                            'value' => function($model) {
                                return $model->last_time_miss;
                            }
                        ],
                        //'last_time_miss_range',
                        //'max_miss',
                        ['attribute' => 'max_miss','headerOptions'=>['width'=>'5%'],'label'=>'最大',
                            'value' => function($model) {
                                return $model->max_miss;
                            }
                        ],
                        //'max_range',
                        //'yl_records:ntext',
                        ['attribute' => 'yl_records','label'=>'遗漏',
                            'value' => function($model) {
                                return $model->yl_records;
                            }
                        ],
                        //'history_max_miss',
                        ['attribute' => 'history_max_miss','headerOptions'=>['width'=>'5%'],'label'=>'最大',
                            'value' => function($model) {
                                return $model->history_max_miss;
                            }
                        ],
                        //'codes_hz',
                        ['attribute' => 'codes_hz','headerOptions'=>['width'=>'5%'],'label'=>'和值',
                            'value' => function($model) {
                                return $model->codes_hz;
                            }
                        ],
                        ['attribute' => 'type_2b','headerOptions'=>['width'=>'5%'],'label'=>'两兄弟',
                            'value' => function($model) {
                                return $model->type_3b;
                            }
                        ],
                        //'type_3b',
                        ['attribute' => 'type_3b','headerOptions'=>['width'=>'5%'],'label'=>'三兄弟',
                            'value' => function($model) {
                                return $model->type_3b;
                            }
                        ],
                        //'type_3',
                        ['attribute' => 'type_3','headerOptions'=>['width'=>'5%'],'label'=>'三重',
                            'value' => function($model) {
                                return $model->type_3;
                            }
                        ],
                        //'type_4',
                        ['attribute' => 'type_4','headerOptions'=>['width'=>'5%'],'label'=>'四重',
                            'value' => function($model) {
                                return $model->type_4;
                            }
                        ],
                        //'type_22',
                        ['attribute' => 'type_22','headerOptions'=>['width'=>'5%'],'label'=>'双双重',
                            'value' => function($model) {
                                return $model->type_22;
                            }
                        ],
                        //'type_4ds',
                        ['attribute' => 'type_4ds','headerOptions'=>['width'=>'5%'],'label'=>'四单双',
                            'value' => function($model) {
                                return $model->type_4ds;
                            }
                        ],
                        //'count',
                        ['attribute' => 'count','headerOptions'=>['width'=>'5%'],'label'=>'总组数',
                            'value' => function($model) {
                                return $model->count;
                            }
                        ],
                        //'static_nums',
                        //'theory_nums_perdate',
                        //'today_nums',
                        //'ytd_nums',
                        //'lottery_type',
                        //'status',
                        //'created_at',
                        //'updated_at',
                        //'update_time',
                        ['attribute' => 'update_time','headerOptions'=>['width'=>'5%'],'label'=>'时间',
                            'value' => function($model) {
                                return substr($model->update_time, 5);
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
        type = $(this).data('type');
        lottery_type = $(this).data('lottery_type');
        console.log(val, type, lottery_type);
        $.post('/forum/ssc-static-yl/get-val-static', {val:val,type:type,lottery_type:lottery_type}, function(rst) {
            $('#tip_msg_rst').html('<strong>号码：</strong>'+val + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
            $('#rstTipModal').modal('show');
        });
    });
})
</script>
