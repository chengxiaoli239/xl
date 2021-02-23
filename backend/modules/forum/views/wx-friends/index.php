<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\BaseStringHelper;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\WxFriends */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Wx Friends');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="wx-friends-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Weixin Login'), ['login'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>
                -->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'Uin',
                        //'UserName',
                        //'NickName',
                        ['attribute'=>'NickName','label'=>'昵称',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return urldecode($model->NickName);
                            }
                        ],
                        //'HeadImgUrl',
                        ['attribute'=>'HeadImgUrl','label'=>'头像','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>['image',['height'=>30, 'width'=>30]],
                            'value'=>function($model){
                                return \Yii::$app->params['WX_IMG_URL_DOMAIN'].$model->HeadImgUrl;
                            }
                        ],
                        //'ContactFlag',
                        //'MemberCount',
                        //'MemberList:ntext',
                        //'RemarkName',
                        ['attribute' => 'RemarkName', 'format'=>'raw',
                            'value' => function($model) {
                                $dataArr = [0=>'未知', 1=>'男', 2=>'女'];
                                return $dataArr[$model->Sex];
                            }
                        ],
                        //'HideInputBarFlag',
                        //'Signature',
                        ['attribute' => 'Signature', 'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->Signature,25);
                                return Html::a($txt, 'javascript:;', ['title' => $model->Signature,'alt'=>$model->Signature]);
                            }
                        ],
                        'send_name',
                        ['attribute' => 'status','label'=>'是否发送消息',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $type_Arr = [0=>'已关闭', 1=>'已开启'];
                                $txt = $type_Arr[$model->status];
                                $url0 = "/forum/wx-friends/switch-status?id=".$model->id.'&status=1'; # 切换开启
                                $url1 = "/forum/wx-friends/switch-status?id=".$model->id.'&status=0'; # 切换关闭
                                if($model->status == 1){
                                    $txt = "<font color='green'>$txt</font>" ;
                                    return Html::a($txt, $url1, ['title' => '切换关闭']);
                                }
                                if(!$model->status){
                                    $txt = "<font color='red'>$txt</font>";
                                    return Html::a($txt, $url0, ['title' => '切换开启']);
                                }
                            }
                        ],
                        //'VerifyFlag',
                        //'OwnerUin',
                        //'PYInitial',
                        //'PYQuanPin',
                        //'RemarkPYInitial',
                        //'RemarkPYQuanPin',
                        //'StarFriend',
                        //'AppAccountFlag',
                        //'Statues',
                        //'AttrStatus',
                        'Province',
                        'City',
                        //'Alias',
                        //'SnsFlag',
                        //'UniFriend',
                        //'DisplayName',
                        //'ChatRoomId',
                        //'KeyWord',
                        //'EncryChatRoomId',
                        //'IsOwner',
                        //'created_at',
                        'updated_at:datetime',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
