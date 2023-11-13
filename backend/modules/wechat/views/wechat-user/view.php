<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\WechatUser */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Wechat Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="wechat-user-view wrapper site-min-height">
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <?= Html::encode($this->title) ?>
                </header>
                <div class="panel-body">
                    <p>
                        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger',
                            'data' => [
                                'confirm' => 'Are you sure you want to delete this item?',
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
                                    'user_id',
                                    'userName',
                                    'nickName',
                                    'aliasName',
                                    'status',
                                    'balance',
                                    'is_credit',
                                    'bigHead',
                                    'smallHead',
                                    'labelList',
                                    'remark',
                                    'expire_time:datetime',
                                    'created_at',
                                    'updated_at',
                                    'update_at',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
