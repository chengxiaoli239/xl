<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticCode4nAriseMonth */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Code4n Arise Months'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static-code4n-arise-month-view wrapper site-min-height">
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
                                    'month',
                                    'code_0145',
                                    'code_0137',
                                    'code_1256',
                                    'code_2348',
                                    'code_3567',
                                    'code_3478',
                                    'code_0678',
                                    'code_0347',
                                    'code_5689',
                                    'code_0138',
                                    'code_0189',
                                    'code_0139',
                                    'code_0125',
                                    'code_1367',
                                    'code_1348',
                                    'code_0129',
                                    'code_1378',
                                    'code_1359',
                                    'code_3589',
                                    'code_0149',
                                    'code_0478',
                                    'code_5789',
                                    'code_1238',
                                    'code_1267',
                                    'code_1234',
                                    'code_2367',
                                    'code_2569',
                                    'code_1469',
                                    'code_1269',
                                    'code_4679',
                                    'code_0258',
                                    'code_0267',
                                    'code_0369',
                                    'code_0567',
                                    'code_1568',
                                    'code_2567',
                                    'code_2457',
                                    'code_0259',
                                    'code_2356',
                                    'code_4789',
                                    'code_0148',
                                    'code_0136',
                                    'code_1678',
                                    'code_2358',
                                    'code_0569',
                                    'code_0278',
                                    'code_2478',
                                    'code_0247',
                                    'code_1379',
                                    'code_0239',
                                    'code_1136',
                                    'code_2899',
                                    'code_0448',
                                    'code_4668',
                                    'code_5889',
                                    'code_1179',
                                    'code_1159',
                                    'code_1227',
                                    'code_2247',
                                    'code_0014',
                                    'code_1168',
                                    'code_0013',
                                    'code_3559',
                                    'code_4457',
                                    'code_1366',
                                    'code_0037',
                                    'code_3346',
                                    'code_7899',
                                    'code_1889',
                                    'code_2477',
                                    'code_0466',
                                    'code_1899',
                                    'code_6889',
                                    'code_4489',
                                    'code_0499',
                                    'code_0899',
                                    'code_0477',
                                    'code_3347',
                                    'code_2344',
                                    'code_0488',
                                    'code_0229',
                                    'code_7789',
                                    'code_1124',
                                    'code_0114',
                                    'code_4456',
                                    'code_0016',
                                    'code_1149',
                                    'code_3799',
                                    'code_1499',
                                    'code_3367',
                                    'code_3499',
                                    'code_0025',
                                    'code_2447',
                                    'code_0017',
                                    'code_3348',
                                    'code_0115',
                                    'code_1228',
                                    'code_1778',
                                    'code_2388',
                                    'code_3577',
                                    'lottery_type',
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
