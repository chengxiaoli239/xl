<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\wechat\Bets */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Bets', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="bets-view wrapper site-min-height">
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
                                    'wechat_user_id',
                                    'order_id',
                                    'play_method',
                                    'play_name',
                                    'codes:ntext',
                                    'bet_money',
                                    'bonus',
                                    'single',
                                    'ratio',
                                    'profits',
                                    'qihao',
                                    'kj_codes',
                                    'status',
                                    'cancel_status',
                                    'is_simulate',
                                    'lottery_name',
                                    'lottery_type',
                                    'is_profits_record',
                                    'bet_desc:ntext',
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
