<?php

use yii\helpers\Html;

// Empty values preserve the existing all-day real betting behavior.
?>
<div class="form-group">
    <label class="control-label">真实投注时间段</label>
    <div>
        <?= Html::activeInput('time', $model, 'real_bet_start_time', ['class' => 'form-control', 'style' => 'display:inline-block;width:130px;', 'title' => '留空表示不限制']) ?>
        <span style="margin:0 8px;">至</span>
        <?= Html::activeInput('time', $model, 'real_bet_end_time', ['class' => 'form-control', 'style' => 'display:inline-block;width:130px;', 'title' => '留空表示不限制']) ?>
        <span class="help-block">时间段外计划仍保持开启，只写模拟记录，不向盘口下注；支持跨午夜时间段。</span>
    </div>
</div>
