<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsInPlay */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sports In Plays'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="sports-in-play-view wrapper site-min-height">
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
                                    'event_id',
                                    'play_type',
                                    'game_court',
                                    'plate_id',
                                    'home_name',
                                    'away_name',
                                    'home_score',
                                    'away_score',
                                    'plate_1X2_odds_1',
                                    'plate_1X2_odds_2',
                                    'plate_1X2_odds_3',
                                    'plate_rolling_home',
                                    'plate_rolling_away',
                                    'bet_url:url',
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
