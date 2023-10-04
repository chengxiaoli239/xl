<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\wechat\Bets */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '3D记录';
$this->params['breadcrumbs'][] = $this->title;
?>
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
                                return ($model->codes OR $model->codes===0 OR $model->codes==='0') ? $model->codes : '待开奖';
                            }
                        ],
                        #'single',
                        ['attribute' => 'single','label'=>'倍数[元]',//'headerOptions'=>['width'=>'5%'],
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
                        'bet_money',
                        //'ratio',
                        ['attribute' => 'bonus','label'=>'中奖',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->bonus>0 ? '<font color="green">'.$model->bonus.'</font>' : ' ';
                            }
                        ],
                        'profits',
                        'qihao',
                        ['attribute' => 'kj_codes','label'=>'开奖', //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->kj_codes ? $model->kj_codes : '<strong><font color="green">待开奖</font></strong>';
                            }
                        ],
                        ['attribute' => 'wechat_user_id','label'=>'微信',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $WechatUser = \common\models\wechat\WechatUser::findOne($model->wechat_user_id);
                                $txt = '<img src="'.$WechatUser->smallHead.'" width="30" height="30"> '.$WechatUser->nickName;
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
                        'bet_desc:ntext',
                        //'created_at',
                        //'updated_at',
                        ['attribute' => 'update_at','label'=>'时间',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return substr($model->update_at, 5, 11);
                            }
                        ],

                        #['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
