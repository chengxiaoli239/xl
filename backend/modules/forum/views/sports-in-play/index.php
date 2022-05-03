<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SportsInPlay */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Sports In Plays');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="sports-in-play-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Sports In Play'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'league_matches_id',
                        'league_matches_name',
                        'event_id',
                        'play_type',
                        //'game_court',
                        //'plate_id',
                        //'home_name',
                        //'away_name',
                        //'home_score',
                        //'away_score',
                        //'plate_1X2_odds_1',
                        //'plate_1X2_odds_2',
                        //'plate_1X2_odds_3',
                        //'plate_rolling_home',
                        //'plate_rolling_away',
                        //'bet_url:url',
                        //'plate_bet_conditions',
                        //'desc:ntext',
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
