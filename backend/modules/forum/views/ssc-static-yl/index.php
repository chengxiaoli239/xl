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
                                    'data-val' => $model->val,
                                    'data-lottery_type' => Yii::$app->request->queryParams['SscStaticYl']['lottery_type'],
                                ];
                                return Html::a($txt, 'javascript:;', $options);
                            }
                        ],
                        'current_miss',
                        'last_time_miss',
                        //'last_time_miss_range',
                        'max_miss',
                        //'max_range',
                        'yl_records:ntext',
                        'history_max_miss',
                        'count',
                        //'static_nums',
                        'theory_nums_perdate',
                        'today_nums',
                        'ytd_nums',
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
<div id="modalTable" class="modal fade" tabindex="-1" role="dialog" style="display: none;padding-right: -10px;" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalTable-head" style="height: 45px;">
                <h5 class="modal-title" style="" id="modalTable-title">Modal 表</h5>
                <button type="button" class="close" data-dismiss="modal" style="margin-top: -23px;" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 0px;">
                <div class="bootstrap-table bootstrap4">
                    <div class="fixed-table-toolbar"></div>
                    <div class="fixed-table-container fixed-height" style="height: 599px; padding-bottom: 50px;">
                        <div class="fixed-table-header" style="margin-right: 0px;">
                            <table class="table table-bordered table-hover" style="">
                                <thead style="">
                                <tr>
                                    <td data-field="id" class="th-inner" style="width: 40px;"> </td>
                                    <td data-field="gamenum" class="th-inner" style="width: 75px;">期号</td>
                                    <td data-field="water" class="th-inner" style="width: 70px;">回水</td>
                                    <td data-field="summoney" class="th-inner" style="width: 70px;">下分</td>
                                    <td data-field="okmoney" class="th-inner" style="width: 70px;">结算</td>
                                    <td data-field="profits" class="th-inner" style="width: 70px;">盈亏</td>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="fixed-table-body">
                            <div class="fixed-table-loading table table-bordered table-hover fixed-table-border" style="display: none; width: 100%;">
                              <span class="loading-wrap">
                                  <span class="loading-text">Loading, please wait</span>
                                  <span class="animation-wrap"><span class="animation-dot"></span></span>
                              </span>
                            </div>
                            <table id="table" data-toggle="bootstrap-table" data-height="499" data-url="json/data1.json" class="table table-bordered table-hover" style="margin-top: -3px;">
                                <tbody id="tbody-content">
                                <!--
                                <tr data-index="1"><td style="">1</td><td style="width:16px;">Item 1</td><td style="">$1</td><td style="">1</td><td style="">Item 1</td><td style="">$1</td></tr>
                                <tr data-index="2"><td style="">2</td><td style="width:16px;">Item 2</td><td style="">$2</td><td style="">2</td><td style="">Item 2</td><td style="">$2</td></tr>
                                <tr data-index="3"><td style="">3</td><td style="width:16px;">Item 3</td><td style="">$3</td><td style="">3</td><td style="">Item 3</td><td style="">$3</td></tr>
                                <tr data-index="4"><td style="">4</td><td style="width:16px;">Item 4</td><td style="">$4</td><td style="">4</td><td style="">Item 4</td><td style="">$4</td></tr>
                                <tr data-index="5"><td style="">5</td><td style="width:16px;">Item 5</td><td style="">$5</td><td style="">5</td><td style="">Item 5</td><td style="">$5</td></tr>
                                <tr data-index="6"><td style="">6</td><td style="width:16px;">Item 6</td><td style="">$6</td><td style="">6</td><td style="">Item 6</td><td style="">$6</td></tr>
                                <tr data-index="7"><td style="">7</td><td style="width:16px;">Item 0</td><td style="">$0</td><td style="">0</td><td style="">Item 0</td><td style="">$0</td></tr>
                                <tr data-index="8"><td style="">8</td><td style="width:16px;">Item 0</td><td style="">$0</td><td style="">0</td><td style="">Item 0</td><td style="">$0</td></tr>
                                <tr data-index="9"><td style="">9</td><td style="width:16px;">Item 0</td><td style="">$0</td><td style="">0</td><td style="">Item 0</td><td style="">$0</td></tr>
                                -->
                                </tbody>
                            </table>
                            <div class="fixed-table-border" style="height: 0px;"></div>
                        </div>
                        <div class="fixed-table-footer" style="display: none;">
                            <table><thead><tr></tr></thead></table>
                        </div>
                    </div>
                    <div class="fixed-table-pagination" style="display: none;"></div>
                </div><div class="clearfix"></div>
            </div>
            <div class="modal-footer">
                <!--
                <button type="button" class="btn btn-secondary act-data-type act-data-type-0" data-type="0">今日</button>
                <button type="button" class="btn btn-secondary act-data-type act-data-type-1" data-type="1">昨日</button>
                <button type="button" class="btn btn-secondary act-data-type act-data-type-2" data-type="2">前天</button>
                -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
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
                $('#tip_msg_rst').html('<strong>号码：</strong>'+rst.val_desc + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
                $('#rstTipModal').modal('show');
            });
        });
    })
</script>
