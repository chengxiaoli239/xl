<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\LotteryType */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Lottery Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="lottery-type-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Lottery Type'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'sort',
                        //'lottery_type',
                        //'title',
                        ['attribute' => 'title','label'=>'名称',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->title;
                            }
                        ],
                        ['attribute' => 'lottery_type','label'=>'系统lottery_type',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                //return  \backend\service\Config_Base::lotteryTypeLists($model->lottery_type);
                                return  $model->lottery_type;
                            }
                        ],
                        //'shortName',
                        ['attribute' => 'shortName','label'=>'简称',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->shortName;
                            }
                        ],
                        //'enable',
                        ['attribute' => 'enable','label'=>'开启状态','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url0 = "/forum/lottery-type/switch-status?id=".$model->id.'&enable=1'; # 点击开启
                                $url1 = "/forum/lottery-type/switch-status?id=".$model->id.'&enable=0'; # 点击关闭
                                if($model->enable == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->enable){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                //return \backend\service\Config_Base::dropDown('enable', $model->enable);
                            },
                            'filter' => \backend\service\Config_Base::dropDown('enable'),
                        ],
                        //'isDelete',
                        //'name',
                        //'codeList',
                        ['attribute' => 'codeList','label'=>'号码',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->codeList;
                            }
                        ],
                        //'info',
                        ['attribute' => 'info','label'=>'描述',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->info;
                            }
                        ],
                        ['attribute'=>'status', 'label'=>'操作',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url1 = '/forum/lottery-type/init-lottery'; # 路由
                                $txt = "<button data-url='".$url1."' color='green' data-lottery-type='".$model->lottery_type."' class='button btn-success act-execute'>初始化</button>" ;
                                return Html::a($txt, 'javascript:;', ['title' => '点击执行']);
                            }
                        ],
                        //'onGetNoed',
                        ['attribute' => 'onGetNoed','label'=>'事件函数',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->onGetNoed;
                            }
                        ],
                        ['attribute' => 'data_ftime','label'=>'时间间隔(s)',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->data_ftime;
                            }
                        ],
                        //'defaultViewGroup',
                        //'android',
                        //'num',
                        ['attribute' => 'typeGroupName','label'=>'彩种类别',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->typeGroupName;
                            }
                        ],

                        ['class' => 'yii\grid\ActionColumn','headerOptions'=>['width'=>'5%'],'template'=>'{update}&nbsp;&nbsp;&nbsp;&nbsp;{delete}'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<div class="modal fade" id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">提示信息</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>推送结果：</strong>
                    <pre><code id="rst_code"></code></pre>
                    <strong>推送内容：</strong>
                    <pre><code id="push_content"></code></pre>
                </form>
            </div>
            <div class="modal-footer">
                <button id="copyBarcodes" type="button" class="btn btn-primary">复制链接</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">确定</button>
            </div>
        </div>
    </div>
</div>
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
$(function () {
    $('.act-execute').click(function () {
        domain = window.location.protocol + "//" + window.location.host;
        url = $(this).data('url');
        lottery_type = $(this).data('lottery-type');
        console.log(domain)
        $.post(url, {lottery_type: lottery_type}, function (rst) {
            $('#rst_code').text(JSON.stringify(rst, null, ' '))
            //$('#push_content').text('curl ' + domain + url)
            $('#exampleModal_msg').modal('show');
        });
    });
})
</script>
