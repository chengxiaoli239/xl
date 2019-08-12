<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\KjConfig */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Kj Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="kj-config-view wrapper site-min-height">
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
                                    'title',
                                    'name',
                                    'host',
                                    'api_host',
                                    'path',
                                    'is_batch',
                                    'lottery_type',
                                    'method',
                                    'post_data',
                                    'data_type',
                                    'enable',
                                    'created_at',
                                    'updated_at',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
