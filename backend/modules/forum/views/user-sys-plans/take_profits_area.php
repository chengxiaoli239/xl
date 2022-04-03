<!--区间盈利，一个区间遗漏次数满足起投，每次起投止盈止损-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color:#3c3c3c">
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'area_all_qishus')->textInput()->label('区间统计期数') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'area_yl_qishus')->textInput()->label('区间遗漏期数') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'area_profits')->textInput()->label('区间止盈(例：3000)') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'area_loss')->textInput()->label('区间止损(例：4000)') ?>
    </div>
</div>
