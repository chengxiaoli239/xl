<?= $form->field($model, 'change_per')->checkboxList([1=>'是'])->label('每期轮换') ?>
<div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model,"import_codes_txts[1]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('1.号码一：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
</div>
<div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model,"import_codes_txts[2]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('2.号码二：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
</div>
<div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model,"import_codes_txts[3]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('3.号码三：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
</div>
<div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model,"import_codes_txts[4]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('4.号码四：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
</div>
<div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model,"import_codes_txts[5]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('5.号码五：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
</div>
