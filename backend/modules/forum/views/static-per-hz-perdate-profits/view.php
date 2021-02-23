<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticPerHzPerdateProfits */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Per Hz Perdate Profits'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static-per-hz-perdate-profits-view wrapper site-min-height">
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
                                    'date',
                                    'codes_1',
                                    'codes_2',
                                    'codes_3',
                                    'codes_4',
                                    'codes_5',
                                    'codes_6',
                                    'codes_7',
                                    'codes_8',
                                    'codes_9',
                                    'codes_10',
                                    'codes_11',
                                    'codes_12',
                                    'codes_13',
                                    'codes_14',
                                    'codes_15',
                                    'codes_16',
                                    'codes_17',
                                    'codes_18',
                                    'codes_19',
                                    'codes_20',
                                    'codes_21',
                                    'codes_22',
                                    'codes_23',
                                    'codes_24',
                                    'codes_25',
                                    'codes_26',
                                    'codes_27',
                                    'codes_28',
                                    'codes_29',
                                    'codes_30',
                                    'codes_31',
                                    'codes_32',
                                    'codes_33',
                                    'codes_34',
                                    'codes_35',
                                    'codes_36',
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
