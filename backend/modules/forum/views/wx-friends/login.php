<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\WxFriends */

$this->title = Yii::t('app', 'Wx Login'); # $model->id
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Wx Friends'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="wx-friends-view wrapper site-min-height">
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <?= Html::encode($this->title) ?>
                </header>
                <div class="panel-body">
                    <p>
                        <!--
                        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger',
                            'data' => [
                                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]) ?>
                        -->
                    </p>
                    <div class="row">
                        <div class="col-lg-11">
                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => [

                                    //'id',
                                    //'Uin',
                                    //'UserName',
                                    //'NickName',
                                    //'HeadImgUrl',
                                    ['attribute'=>'HeadImgUrl','label'=>'二维码','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                                        'format'=>['image',['height'=>300, 'width'=>300]],
                                        'value'=>function($model){
                                            return $model->login_img;
                                        }
                                    ],
                                    /*
                                    'ContactFlag',
                                    'MemberCount',
                                    'MemberList:ntext',
                                    'RemarkName',
                                    'HideInputBarFlag',
                                    'Sex',
                                    'Signature',
                                    'VerifyFlag',
                                    'OwnerUin',
                                    'PYInitial',
                                    'PYQuanPin',
                                    'RemarkPYInitial',
                                    'RemarkPYQuanPin',
                                    'StarFriend',
                                    'AppAccountFlag',
                                    'Statues',
                                    'AttrStatus',
                                    'Province',
                                    'City',
                                    'Alias',
                                    'SnsFlag',
                                    'UniFriend',
                                    'DisplayName',
                                    'ChatRoomId',
                                    'KeyWord',
                                    'EncryChatRoomId',
                                    'IsOwner',
                                    */
                                    //'created_at',
                                    ['attribute' => 'created_at', 'label'=>'时间',
                                        'value' => function($model) {
                                            return date('Y-m-d H:i:s', $model->created_at);
                                        }
                                    ],
                                    //'updated_at',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
<input type="hidden" id="uuid" value="<? echo $uuid;?>">
<script src="/statics/datetimepicker/jquery.js"></script>
<script>
$(function () {
})
var t2 = window.setInterval(function (){
    uuid = $("#uuid").val();
    $.post("/forum/wx-friends/get-login-status", {uuid:uuid}, function(rst) {
        console.log(rst);
        if(rst.code == 200){
            window.location.href = '/forum/wx-friends/index';
        }
    });
},10000);
</script>
