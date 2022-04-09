<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\DataDealStatus */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Data Deal Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="data-deal-status-view wrapper site-min-height">
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
                                    'lottery_type',
                                    'qihao',
                                    'next_qihao',
                                    'status',
                                    'status_desc',
                                    'static4dPerDateProfits_status',
                                    'static4dPerDateProfits_status_desc',
                                    'updateDs_status',
                                    'updateDs_status_desc',
                                    'updateDsYL_status',
                                    'updateDsYL_status_desc',
                                    'update3NumYL_status',
                                    'update3NumYL_status_desc',
                                    'updateSdHzYL_status',
                                    'updateSdHzYL_status_desc',
                                    'opProfitsPlans_status',
                                    'opProfitsPlans_status_desc',
                                    'created_at',
                                    'updated_at',
                                    'update_time',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
