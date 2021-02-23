<?php

use yii\helpers\Html;
use backend\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\SscKjData */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Kj Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-kj-data-view wrapper site-min-height">
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
                                    'kj_code',
                                    'code_str',
                                    'code1',
                                    'code2',
                                    'code3',
                                    'code4',
                                    'code5',
                                    'code_1_2',
                                    'code_1_3',
                                    'code_1_4',
                                    'code_2_3',
                                    'code_2_4',
                                    'code_3_4',
                                    'qihao',
                                    'date',
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
