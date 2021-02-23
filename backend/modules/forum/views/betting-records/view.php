<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\BettingRecords */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Betting Records', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="betting-records-view wrapper site-min-height">
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
                                    'codes',
                                    'account',
                                    'playway',
                                    'playway_name',
                                    'betting_money',
                                    'bonus',
                                    'single',
                                    'profits',
                                    'qihao',
                                    'kj_codes',
                                    'position',
                                    'status',
                                    'sn',
                                    'snid',
                                    'is_simulate',
                                    'lotteryclass',
                                    'createtime:datetime',
                                    'create_time',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
