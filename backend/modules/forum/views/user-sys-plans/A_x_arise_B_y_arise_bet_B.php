<!--过滤前x期的号码，或者前一个区间内的号码-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #da4f49;">
    <div class="col-lg-6 col-xs-12">
        <?= $form->field($model,"import_codes_txts[arise_A_codes]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('号码A：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
    <div class="col-lg-6 col-xs-12">
        <?= $form->field($model,"import_codes_txts[arise_B_codes]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('号码B：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
    <div class="col-lg-6 col-xs-6">
        <?= $form->field($model, 'arise_A_times')->textInput(['type' => 'number', 'min' => 1])->label('A出x次数（至少1次）') ?>
    </div>
    <div class="col-lg-6 col-xs-6">
        <?= $form->field($model, 'arise_B_times')->textInput(['type' => 'number', 'min' => 0])->label('B出y次数（可填0）') ?>
    </div>
</div>
