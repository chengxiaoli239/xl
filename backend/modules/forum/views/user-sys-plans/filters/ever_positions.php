<!--任意位置(不定位)-99-->
<div class="row filter-mode-p" style="border-width:2px;margin-top:3px;border-style:solid;border-color: green;" id="filter-mode-p">
    <ul id="filter-mode-ul">
        <li>
            <div class="col-lg-10 col-xs-10" class="filter_mode">
                <div class="col-lg-3 col-xs-5">
                    <?= $form->field($model, 'arb_pos_isbaohan[]')->checkboxList([0=>'否', 1=>'是'])->label('任意包含') ?>
                </div>
                <div class="col-lg-3 col-xs-4">
                    <?= $form->field($model, 'arb_pos_codes[]')->textInput()->label('号码') ?>
                </div>
                <div class="col-lg-2 col-xs-3">
                    <?= $form->field($model, 'arb_pos_nums[]')->textInput()->label('个数') ?>
                </div>
            </div>
        </li>
    </ul>
    <div class="col-lg-2 col-xs-1">
        <div class="form-group field-usersysplans-add">
            <label class="control-label"></label>
            <input type="hidden" name="UserSysPlans_add" value="">
            <div id="usersysplans-add">
                <label><?= \yii\helpers\Html::button('+', ['type'=>'button', 'class'=>'btn btn-xs btn-success', 'id'=>'filter-add']) ?></label>
            </div>
            <div class="help-block"></div>
        </div>
    </div>
</div>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
$("#filter-add").click(function(){
    console.log('add')
    var $li = $("#filter-mode-ul li:first").clone(true);
    $($li).appendTo("#filter-mode-ul");
})
</script>