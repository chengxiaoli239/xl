<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Static4dProfits */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static4d Profits'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static4d-profits-view wrapper site-min-height">
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
                                    'codes_4d_all',
                                    'codes_13_31',
                                    'codes_22_22',
                                    'codes_1111_2222',
                                    'codes_13',
                                    'codes_31',
                                    'codes_13_2222',
                                    'codes_31_1111',
                                    'codes_31_2222',
                                    'codes_13_1111',
                                    'codes_31_2222_1111',
                                    'codes_13_1111_2222',
                                    'codes_2222',
                                    'codes_1111',
                                    'codes_1_nums',
                                    'codes_2_nums',
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
