<?php

use yii\helpers\Html;
use backend\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\SscDwHzYl */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dw Hz Yls'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-dw-hz-yl-view wrapper site-min-height">
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
                                    'positions',
                                    'zhi',
                                    'current_miss',
                                    'last_time_miss:datetime',
                                    'last_time_miss_range',
                                    'max_miss',
                                    'max_range',
                                    'history_max_miss',
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
