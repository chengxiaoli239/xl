<link rel="stylesheet" href="/vendors/layui/2.4.5/css/modules/layer/default/layer.css?v=3.1.1">
<link rel="stylesheet" href="/vendors/layui/2.5.4/css/layui.css?v=2020">
<link rel="stylesheet" href="/css/layui/global.css?v={{STATIC_VERSION}}">
<script type="text/javascript" src="/vendors/layui/2.4.5/layui.js"></script>
<script type="text/javascript" src="/vendors/layui-layer/3.1.1/layer.js"></script>
<script type="text/javascript" src="/vendors/atrtemplate/4.13.2/template-web.js"></script>
<script type="text/javascript" src="/statics/js/jquery-2.0.3.js"></script>
<script type="text/javascript" src="/js/layui/global.js?v={{STATIC_VERSION}}"></script>
<script type="text/javascript" src="/js/common.js?v={{STATIC_VERSION}}"></script>
<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\statics\Static3dUserProfitsDayAll */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '日报表-总';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static3d-user-profits-day-all-index wrapper site-min-height">
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

                        //'id',
                        'date',
                        //'user_id',
                        ['attribute' => 'user_id', 'label'=>'代理', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return $model->proxy->username;
                            },
                        ],
                        #'wechat_user_id',
                        ['attribute' => 'wechat_user_name', 'label'=>'客户', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                $WechatUser = \common\models\wechat\WechatUser::findOne($model->wechat_user_id);
                                return Html::img($WechatUser->smallHead, ['width' => '30px']) . $WechatUser->nickName;
                            },
                        ],
                        ['attribute' => 'wechat_user_name', 'label'=>'微信ID', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                $WechatUser = \common\models\wechat\WechatUser::findOne($model->wechat_user_id);
                                return $model->wechat_user_name.($WechatUser->remark?'【'.$WechatUser->remark.'】':'');
                            },
                        ],
                        #'bet_money',
                        ['attribute' => 'up_money', 'label'=>'上分', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return $model->up_money;
                            },
                        ],
                        ['attribute' => 'down_money', 'label'=>'下分', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return $model->down_money;
                            },
                        ],
                        ['attribute' => 'bet_money', 'label'=>'投分', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return $model->bet_money;
                            },
                        ],
                        //'bonus',
                        ['attribute' => 'bonus', 'label'=>'中奖', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return $model->bonus;
                            },
                        ],
                        //'profits',
                        ['attribute' => 'profits', 'label'=>'利润', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return '<strong>'.($model->profits>0?'<font color="red">'.$model->profits.'</font>':'<font color="green">'.$model->profits.'</font>').'</strong>';
                            },
                        ],
                        //'created_at',
                        //'updated_at',
                        //'update_time',
                        ['attribute' => 'update_time', 'label'=>'时间', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return substr($model->update_time, 5, 11);
                            },
                        ],
                        ['attribute' => 'profits', 'label'=>'操作', //'headerOptions' => ['width' => '5%'],
                            'format' => 'raw',
                            'value'=> function($model){
                                $WechatUser = \common\models\wechat\WechatUser::findOne($model->wechat_user_id);
                                return '<div class="btn btn-success btn-xs" id="reCalculateConfirm" data-nickName="'.$WechatUser->nickName.'" data-wechatUserId="'.$model->wechat_user_id.'" data-date="'.$model->date.'">重新计算</div>';
                            },
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<script src="/statics/datetimepicker/jquery.js"></script>
<script>
$(function (){
    function reCalculate(wechatUserId, date, nickName) {
        var data = {wechatUserId:wechatUserId, date:date, 'nickName':nickName};
        console.log(data)
        // 弹出确认对话框
        Ewin.confirm({ message: '确认重新新计算 "'+nickName+'" 报表'}).on(function (e) {
            url = '/statics/static3d-user-profits-day-all/re-calculate'
            $.post(url, data, function(rst) {
                Ewin.confirm({ message: rst.msg}).on(function (e) {});
            });
        });
    }

    $(document).on('click', '[id="reCalculateConfirm"]', function () {
        wechatUserId = $(this).attr('data-wechatUserId');
        date = $(this).data('date');
        nickName = $(this).attr('data-nickName');
        reCalculate(wechatUserId, date, nickName)
    });
})
</script>
