<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\StaticHzProfits */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Hz Profits'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static-hz-profits-view wrapper site-min-height">
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
                                    'hz_0_4',
                                    'hz_5_10',
                                    'hz_11_15',
                                    'hz_16_19',
                                    'hz_20_24',
                                    'hz_25_29',
                                    'hz_30_35',
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
