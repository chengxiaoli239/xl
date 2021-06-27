<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: green;">
    <?= $form->field($model, 'change_per')->checkboxList([1=>'是'])->label('每期轮换') ?>
<?php for($i=1; $i<=10; $i++){?>
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
    $('#usersysplans-change_per').click(function () {
        $("[name='UserSysPlans[change_per][]']").each(function () {
            flag = $(this).prop('checked');
            console.log(flag);
            if(flag === true){
                console.log('aaaa');
                $(".import_codes_txts").each(function () {
                    $(this).removeClass('hide')
                    $(this).show()
                });
            }else {
                $(".import_codes_txts").each(function () {
                    $(this).hide();
                });
            }
        });
    });
</script>