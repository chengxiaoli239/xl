<?php

use backend\models\thirdD\BetsBackend;
use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\wechat\Bets */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '3D记录';
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
    /* 默认的弹框大小 */
    .modal-lg {
        width: 65%;
        height: 30%;
        margin: 100px auto;
    }

    /* 在小屏幕上设置较大的弹框大小 */
    @media (max-width: 768px) {
        .modal-lg {
            width: 90%;
            height: 30%;
            margin: 50px auto;
        }
    }
</style>
<section class="bets-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <?php echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        #'user_id',
                        'order_id',
                        #'wechat_user_id',
                        #'codes:ntext',
                        ['attribute' => 'codes','label'=>'号码', //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = ($model->codes OR $model->codes===0 OR $model->codes==='0') ? $model->codes : '';
                                return Html::a(BaseStringHelper::truncate($txt,25), 'javascript:;', [
                                    'class'=>'act-post-desc',
                                    'title'=>$model->codes,
                                    'alt'=>str_replace('@', ',',str_replace(',', '',$model->codes)),
                                ]);
                            }
                        ],
                        #'single',
                        ['attribute' => 'single','label'=>'倍[元]',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->single;
                            }
                        ],
                        ['attribute' => 'count','label'=>'组数',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->count;
                            }
                        ],
                        ['attribute' => 'push_status','label'=>'盘口',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $title = $model->push_status==BetsBackend::PUSH_STATUS_WAIT ? '2分钟自动推盘口' : '';
                                $title = $model->push_status==BetsBackend::PUSH_STATUS_FAIL ? $model->push_desc : $title;
                                return '<a href="javascript:;" title="'.$title.'"><strong><font color="'.BetsBackend::PUSH_STATUS_CLASSES[$model->push_status].'">'.BetsBackend::PUSH_STATUS_OPTIONS[$model->push_status].'</font></strong></a>';
                            }
                        ],
                        'bet_money',
                        //'ratio',
                        ['attribute' => 'bonus','label'=>'中奖',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return ($model->bonus>0) ? '<font color="green">'.$model->bonus.'</font>' : ' ';
                            }
                        ],
                        'profits',
                        'qihao',
                        ['attribute' => 'kj_codes','label'=>'开奖', //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->status==3? '<strong><font color="red">已撤单</font></strong>' :
                                    (($model->status===0) ? '<strong><font color="green">待开奖</font></strong>' : $model->kj_codes);
                                return $txt;
                            }
                        ],
                        ['attribute' => 'wechat_user_id','label'=>'微信',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $WechatUser = \common\models\wechat\WechatUser::findOne($model->wechat_user_id);
                                $txt = '<img src="'.$WechatUser->smallHead.'" width="30" height="30" title="'.$WechatUser->userName.'"> <a href="javascript:;" title="'.$WechatUser->userName.'">'.$WechatUser->nickName.'</a>';
                                return $txt;
                            }
                        ],
                        #'play_method',
                        ['attribute' => 'lottery_name','label'=>'玩法', //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $playMethod = \common\service\CommonService::getPlayMethods()[$model->play_method];
                                return \common\service\CommonService::getLotteryName($model->lottery_type).'['.$playMethod.']';
                            }
                        ],
                        //'status',
                        //'cancel_status',
                        //'is_simulate',
                        //'lottery_name',
                        //'lottery_type',
                        //'is_profits_record',
                        #'bet_desc:ntext',
                        ['attribute' => 'bet_desc','label'=>'文本', //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::a(BaseStringHelper::truncate($model->bet_desc,25), 'javascript:;', [
                                    'class'=>'act-post-desc',
                                    'title'=>$model->bet_desc,
                                    'alt'=>$model->api_code_datas,
                                ]);
                            }
                        ],
                        //'created_at',
                        //'updated_at',
                        ['attribute' => 'update_at','label'=>'时间',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return substr($model->update_at, 5, 11);
                            }
                        ],
                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                    'pager' => [
                        'firstPageLabel' => '首页',  // 您可以根据需要自定义文本
                        'lastPageLabel' => '尾页',  // 您可以根据需要自定义文本
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<div class="modal fade " id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>内容：</strong>
                    <pre><code id="push_content"></code></pre>
                    <strong>接口识别内容：</strong>
                    <pre><code id="api_content"></code></pre>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
    $(function () {
        $(".act-post-desc").click(function (rst) {
            content = $(this).attr('title');
            $('#push_content').text(content)

            api_content = $(this).attr('alt');
            formatted_api_content = JSON.stringify(JSON.parse(api_content), null, 2);
            $('#api_content').text(formatted_api_content);

            $('#exampleModal_msg').modal('show');
        });
    });
</script>
