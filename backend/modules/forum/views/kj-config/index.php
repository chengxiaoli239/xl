<?php

use common\kj\cqssc\CqsscKcw;
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

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'title',
                        //'name',
                        ['attribute' => 'lottery_type','label'=>'彩种类型',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $lotteryNameArr = CqsscKcw::getLotteryNameArr();
                                $str = $lotteryNameArr[$model->lottery_type];
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
                        ['attribute'=>'status', 'label'=>'操作',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url1 = $model->path; # 路由
                                $txt = "<button data-url='".$url1."' color='green' class='button btn-success grab-execute'>执行</button>" ;
                                return Html::a($txt, 'javascript:;', ['title' => '点击执行']);
                            }
                        ],
                        //'lottery_type',

                        //'created_at',
                        ['attribute'=>'updated_at', 'label'=>'更新时间',#'headerOptions'=>['width'=>'5%'],
                            'value'=>function($model){
                                return date('Y-m-d H:i:s', $model->updated_at);
                            }
                        ],

                        ['class' => 'yii\grid\ActionColumn'],
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
    $("#copyBarcodes").click(function(){
        var text = $("#push_content").html();
        console.log(text)
        status = copyTextToClipboard(text);
        console.log(status);
    });
    $('.grab-execute').click(function () {
        domain = window.location.protocol+"//"+window.location.host;
        url = $(this).data('url');
        console.log(domain)
        $.post(url, {is_auto:2}, function(rst) {
            console.log(rst)
            $('#rst_code').text(JSON.stringify(rst, null, ' '))
            $('#push_content').text('curl ' + domain + url)
            $('#exampleModal_msg').modal('show');
        });
    });

    // 复制内容到剪切板
    function copyTextToClipboard (text) {
        var textArea = document.createElement('textarea')
        textArea.style.position = 'fixed'
        textArea.style.top = 0
        textArea.style.left = 0
        textArea.style.width = '2em'
        textArea.style.height = '2em'
        textArea.style.padding = 0
        textArea.style.border = 'none'
        textArea.style.outline = 'none'
        textArea.style.boxShadow = 'none'
        textArea.style.background = 'transparent'
        textArea.value = text
        document.body.appendChild(textArea)
        textArea.select()
        var result = document.execCommand('copy')
        document.body.removeChild(textArea)
        return result
    }})
</script>
