<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $plan backend\models\UserSysPlans */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $currentProfits float 该计划当前累计盈利 */

$this->title = '计划[' . $plan->id . '] 每期盈利记录';
$this->params['breadcrumbs'][] = ['label' => '计划列表', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$currentProfits = isset($currentProfits) ? (float) $currentProfits : 0.00;
?>
<section class="user-sys-plans-period-profits wrapper site-min-height">
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
            <span class="pull-right"><?= Html::a('返回计划列表', ['index', 'UserSysPlans[lottery_type]' => $plan->lottery_type], ['class' => 'btn btn-default btn-sm']) ?></span>
        </header>
        <div class="panel-body">
            <p class="text-right" style="margin-bottom:15px;">
                <strong>当前盈利：</strong>
                <span style="font-size:16px; font-weight:bold; color:<?= $currentProfits >= 0 ? '#0a0' : 'red' ?>;"><?= number_format($currentProfits, 2) ?></span>
            </p>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-striped table-bordered', 'style' => 'table-layout:fixed;'],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn', 'header' => '序号', 'headerOptions' => ['style' => 'width:52px;'], 'contentOptions' => ['style' => 'width:52px;']],
                    ['attribute' => 'qihao', 'label' => '期号', 'headerOptions' => ['style' => 'width:110px;'], 'contentOptions' => ['style' => 'width:110px;']],
                    ['attribute' => 'profit_before', 'label' => '开奖前盈利', 'format' => ['decimal', 2], 'headerOptions' => ['style' => 'width:95px;'], 'contentOptions' => function ($model) {
                        return ['style' => 'width:95px; color:' . ($model->profit_before >= 0 ? '#0a0' : 'red')];
                    }],
                    ['attribute' => 'profit_change', 'label' => '本期盈亏', 'format' => ['decimal', 2], 'headerOptions' => ['style' => 'width:95px;'], 'contentOptions' => function ($model) {
                        return ['style' => 'width:95px; color:' . ($model->profit_change >= 0 ? '#0a0' : 'red')];
                    }],
                    ['attribute' => 'profit_after', 'label' => '开奖后盈利', 'format' => ['decimal', 2], 'headerOptions' => ['style' => 'width:95px;'], 'contentOptions' => function ($model) {
                        return ['style' => 'width:95px; color:' . ($model->profit_after >= 0 ? '#0a0' : 'red')];
                    }],
                    ['attribute' => 'period_bet_amount', 'label' => '投注金额', 'format' => ['decimal', 2], 'headerOptions' => ['style' => 'width:85px;'], 'contentOptions' => ['style' => 'width:85px;']],
                    ['attribute' => 'period_group_count', 'label' => '组数', 'headerOptions' => ['style' => 'width:58px;'], 'contentOptions' => ['style' => 'width:58px;']],
                    ['attribute' => 'period_multiple', 'label' => '倍数', 'format' => ['decimal', 2], 'headerOptions' => ['style' => 'width:58px;'], 'contentOptions' => ['style' => 'width:58px;']],
                    ['attribute' => 'created_at', 'label' => '记录时间', 'format' => ['date', 'php:Y-m-d H:i:s'], 'headerOptions' => ['style' => 'width:155px;'], 'contentOptions' => ['style' => 'width:155px;']],
                ],
                'layout' => "{items}\n<div class='text-right'>{pager}</div>",
                'pager' => [
                    'options' => ['class' => 'pagination'],
                    'prevPageLabel' => '上一页',
                    'nextPageLabel' => '下一页',
                ],
            ]); ?>
        </div>
    </section>
</section>
