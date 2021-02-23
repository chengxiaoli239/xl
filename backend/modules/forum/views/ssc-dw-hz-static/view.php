<?php

use yii\helpers\Html;
use backend\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\SscDwHzStatic */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dw Hz Statics'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-dw-hz-static-view wrapper site-min-height">
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
                                    'qihao',
                                    'periods',
                                    'hz_0',
                                    'hz_1',
                                    'hz_2',
                                    'hz_3',
                                    'hz_4',
                                    'hz_5',
                                    'hz_6',
                                    'hz_7',
                                    'hz_8',
                                    'hz_9',
                                    'hz_10',
                                    'hz_11',
                                    'hz_12',
                                    'hz_13',
                                    'hz_14',
                                    'hz_15',
                                    'hz_16',
                                    'hz_17',
                                    'hz_18',
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
