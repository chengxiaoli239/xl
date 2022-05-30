<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsRelated */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sports Relateds'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="sports-related-view wrapper site-min-height">
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
                                    'uid',
                                    'relate_A_game_id',
                                    'relate_B_game_id',
                                    'relate_type',
                                    'relate_sport_type',
                                    'plate_A_id',
                                    'plate_A_name',
                                    'plate_B_id',
                                    'plate_B_name',
                                    'base_url_A:url',
                                    'base_url_B:url',
                                    'plate_bet_url_A:url',
                                    'plate_bet_url_B:url',
                                    'plate_bet_conditions',
                                    'desc:ntext',
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
