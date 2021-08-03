<style>
    .form-control{
        padding: 0px 3px;
    }
</style>
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: green;">
<div class="row">
    <div class="col-lg-2 col-xs-2">
        <label>任意位(不定)：</label>
    </div>

    <div class="col-lg-2 col-xs-2">
        <?= $form->field($model, 'arb_pos_isbaohan')->checkboxList([1=>'是'])->label('包含') ?>
    </div>
    <div class="col-lg-4 col-xs-4">
        <?= $form->field($model, 'arb_pos_codes')->textInput()->label('号码') ?>
    </div>
    <div class="col-lg-2 col-xs-3">
        <?= $form->field($model, 'arb_pos_nums')->textInput()->label('个数') ?>
    </div>
</div>

<?php $turn_key = \Yii::$app->params['IMPORT_CODES_TURN']; for($i=1; $i<$turn_key; $i++){?>
    <div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
        <div class="col-lg-12 col-xs-12">
            <?= $form->field($model,"import_codes_txts[$i]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('号码'.$i.'：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
        </div>
    </div>
<?}?>
    <!--
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
-->
</div>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>

</script>