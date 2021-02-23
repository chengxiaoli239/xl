<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\BetErrorPlansTask */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Bet Error Plans Tasks'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="bet-error-plans-task-view wrapper site-min-height">
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
                                    'codes:ntext',
                                    'uid',
                                    'agent_id',
                                    'account',
                                    'bet_url:url',
                                    'bet_headers',
                                    'post_datas:ntext',
                                    'playway',
                                    'tz_type',
                                    'playway_name',
                                    'bet_money',
                                    'single',
                                    'qihao',
                                    'kj_codes',
                                    'status',
                                    'sn',
                                    'snid',
                                    'plan_id',
                                    'tz_system_id',
                                    'lotteryclass',
                                    'lottery_type',
                                    'post_desc',
                                    'error_desc',
                                    'updated_time',
                                    'updated_at',
                                    'created_at',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
