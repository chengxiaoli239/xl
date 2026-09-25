<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '三字复式遗漏';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-static-yl-index wrapper site-min-height">
    <section class="panel">
        <header class="panel-heading">
            <?php include __DIR__.'/index_tab.php'; ?>
            <?= Html::encode($this->title) ?>，统计最近 <?= (int)$periods ?> 期
        </header>
        <div class="panel-body">
            <?php include __DIR__.'/code_type_tab.php'; ?>
            <?php include __DIR__.'/_miss_history_assets.php'; ?>

            <?= Html::beginForm(['index'], 'get', ['class' => 'form-inline', 'style' => 'margin-bottom:15px;']) ?>
            <?= Html::hiddenInput('SscStaticYl[lottery_type]', $lottery_type) ?>
            <?= Html::hiddenInput('SscStaticYl[type]', \backend\service\statics\yl\ThreeComboYlService::TYPE) ?>
            <?= Html::hiddenInput('SscStaticYl[code_type]', 11) ?>
            <div class="form-group">
                <?= Html::label('统计期数', 'three-combo-periods') ?>
                <?= Html::input('number', 'periods', $periods, [
                    'id' => 'three-combo-periods',
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 10000,
                    'style' => 'width:120px;',
                ]) ?>
            </div>
            <div class="form-group">
                <?= Html::label('号码', 'three-combo-code') ?>
                <?= Html::textInput('code', $codeFilter, [
                    'id' => 'three-combo-code',
                    'class' => 'form-control',
                    'placeholder' => '如 012，多个用逗号',
                ]) ?>
            </div>
            <?= Html::submitButton('查询', ['class' => 'btn btn-primary']) ?>
            <span class="help-block" style="display:inline-block;margin:0 0 0 10px;">四个位置都属于所选三个数字即命中；统计期数最大10000。</span>
            <?= Html::endForm() ?>

            <div class="table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        ['attribute' => 'code', 'label' => '号码'],
                        [
                            'attribute' => 'current_miss',
                            'label' => '当前遗漏',
                            'value' => static function ($row) use ($periods) {
                                return !empty($row['is_window_limit']) ? '≥'.$periods : $row['current_miss'];
                            },
                        ],
                        ['attribute' => 'last_time_miss', 'label' => '上次遗漏'],
                        ['attribute' => 'max_miss', 'label' => '区间最大遗漏'],
                        ['attribute' => 'hit_count', 'label' => '命中次数'],
                        [
                            'attribute' => 'yl_records',
                            'label' => '遗漏记录',
                            'format' => 'raw',
                            'value' => static function ($row) use ($periods) {
                                $display = !empty($row['is_window_limit']) ? '≥'.$periods : (string)$row['current_miss'];
                                return \backend\helpers\MissHistoryFormatter::render($row['current_miss'], $row['yl_records'], $display);
                            },
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    </section>
</section>
