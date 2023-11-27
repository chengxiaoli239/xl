<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuth */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Eyun Auths', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="eyun-auth-view wrapper site-min-height">
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
                                    'type',
                                    'account',
                                    'password',
                                    'status',
                                    'authorization:ntext',
                                    'callback_url:url',
                                    'base_url:url',
                                    'desc:ntext',
                                    'created_at',
                                    'updated_at',
                                    'update_at',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
