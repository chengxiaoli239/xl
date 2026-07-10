<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: green;">
    <div class="col-lg-2 col-xs-3">
        <?= $form->field($model, 'change_per')->checkboxList([1=>'是'])->label('每期轮换') ?>
    </div>
    <div class="col-lg-2 col-xs-3">
        <?= $form->field($model, 'turn_key')->textInput()->label('开始组') ?>
    </div>
    <div class="col-lg-4 col-xs-6">
        <?= $form->field($model, 'change_turn_pos')->checkboxList(\backend\service\NumService::$pos_to_desc)->label('位置号码指定组数(比如说勾“千”，千位开2下期则投第2组)') ?>
    </div>
</div>
<?php $turn_key = \Yii::$app->params['IMPORT_CODES_TURN']; for($i=1; $i<$turn_key; $i++){?>
<div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model,"import_codes_txts[$i]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('号码'.$i.'：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
</div>
<?}?>
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