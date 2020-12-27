<?php

use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BetErrorPlansTask */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Bet Error Plans Tasks');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="bet-error-plans-task-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'codes:ntext',
                        //['attribute' => 'codes','label' => '号码',
                        //    'format'=>'raw',
                        //    'value' => function($model) {
                        //        $txt = BaseStringHelper::truncate($model->codes,15);
                        //        return Html::a($txt, 'javascript:;', ['title' => $model->codes,'alt'=>$model->codes]);
                        //    }
                        //],
                        //'uid',
                        //['attribute' => 'uid','label' => 'UID',
                        //    'format'=>'raw',
                        //    'value' => function($model) {
                        //        return $model->uid;
                        //    }
                        //],
                        //'agent_id',
                        //'account',
                        ['attribute' => 'account','label' => '账号',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->account."[".$model->uid."]";
                            }
                        ],
                        ['attribute' => 'plan_id','label' => 'planid',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->plan_id;
                            }
                        ],
                        ['attribute' => 'bet_sort_key','label' => 'key',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->bet_sort_key;
                            }
                        ],
                        //'bet_url:url',
                        ['attribute' => 'bet_url','label' => '接口',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->bet_url,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->bet_url,'alt'=>$model->bet_url]);
                            }
                        ],
                        //'bet_headers',
                        ['attribute' => 'bet_headers','label' => '头部',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->bet_headers,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->bet_headers,'alt'=>$model->bet_headers]);
                            }
                        ],
                        //'post_datas:ntext',
                        ['attribute' => 'post_datas','label' => '请求内容',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->post_datas,15);
                                $opions = [
                                    'title' => $model->post_datas,
                                    'alt'=>$model->post_datas,
                                    'data-url' => $model->bet_url,
                                    'data-content' => $model->post_datas,
                                    'data-error' => $model->error_desc,
                                ];
                                return Html::a($txt, 'javascript:;', $opions);
                            }
                        ],
                        //'playway',
                        //['attribute'=>'playway','label'=>'类型',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                        //    'format'=>'raw',
                        //    'value'=>function($model){
                        //        return \backend\service\FilterEnumeRateService::getPlayWayTxt($model->playway);
                        //    },
                            //'filter' => \backend\service\FilterEnumeRateService::getPlayWays()
                        //],
                        //'tz_type',
                        ['attribute' => 'tz_type','label' => '类型',
                            'format'=>'raw',
                            'value' => function($model) {
                                return \backend\service\BetService::getTypeNameByTzType($model->tz_type);
                            }
                        ],
                        //'playway_name',
                        //'bet_money',
                        ['attribute' => 'bet_money','label' => '金额',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->bet_money;
                            }
                        ],
                        //'single',
                        ['attribute' => 'single','label' => '倍[元]',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->single;
                            }
                        ],
                        'qihao',
                        //'kj_codes',
                        //'status',
                        ['attribute' => 'status','label' => '状态',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->status == 2 ? '<font color="green">重推成功</font>' : ($model->status == 3 ? '<font color="#2f4f4f">推送失败</a>' : '<font color="red">未推送</a>');
                                $url = "/forum/user-custom-plans/update-status?id=".$model->id;
                                return Html::a($txt, $url, ['title' => '更新状态'.$model->status]);
                            }
                        ],
                        //'sn',
                        //'snid',
                        /*
                        ['attribute' => 'snid','label' => '单号',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->snid;
                            }
                        ],
                        */
                        //'plan_id',
                        //'tz_system_id',
                        //'lotteryclass',
                        //'lottery_type',
                        ['attribute' => 'lottery_type','label' => '种类',
                            'format'=>'raw',
                            'value' => function($model) {
                                return \backend\service\BetService::getLotteryName($model->lottery_type);
                            }
                        ],
                        //'post_desc',
                        //'error_desc',
                        ['attribute' => 'error_desc','label' => '错误描述',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->error_desc,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->error_desc,'alt'=>$model->error_desc]);
                            }
                        ],
                        //'updated_time',
                        //'updated_at',
                        //'created_at',
                        ['attribute' => 'created_at','label' => '时间',
                            'format'=>'raw',
                            'value' => function($model) {
                                return date('m-d H:i', $model->created_at);
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
<!--提示框-start-->
<div class="modal fade " id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 800px;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>推送结果：</strong>
                    <pre><code id="rst_code"></code></pre>
                    <strong>推送内容：</strong>
                    <pre><code id="push_content"></code></pre>
                </form>
            </div>
            <!--div class="form-group down-reason">
                <p><label>备注信息:</label><input class="form-control" id="message" name="message" /></p>
            </div-->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<!--提示框-end-->
<script>
    $(function () {
        $("[id^='push_desc']").click(function (rst) {
            var a = JSON.parse($(this).attr('title'))
            $('#rst_code').text(JSON.stringify(a.rst,null,' '))
            if($(this).data('type') == 4  && $(this).attr('title').indexOf("alipay") == -1){
                // 支付单
                xml = showXml(a.push_content);
                $('#push_content').html(xml);
            }else {
                $('#push_content').text(JSON.stringify(a.push_content,null,' '))
            }

            $('#exampleModal_msg').modal('show');
        });
    });
</script>
