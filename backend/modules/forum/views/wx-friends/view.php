<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\WxFriends */

$this->title = $model->id;
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
                        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger',
                            'data' => [
                                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]) ?>
                    </p>
                    <div class="row">
                        <div class="col-lg-11">
                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => [

                                    'id',
                                    'uid',
                                    'Uin',
                                    'UserName',
                                    'NickName',
                                    'status',
                                    'send_name',
                                    'HeadImgUrl',
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
                                    'created_at',
                                    'updated_at',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
