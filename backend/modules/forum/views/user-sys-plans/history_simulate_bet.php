<!--每期动态过滤-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #7000cb;">
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_type')->dropDownList([''=>'请选择', 1=>'同位置前x期', 2=>'历史数据'])->label('排除类型') ?>
    </div>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_nums')->textInput()->label('例:2 则排前2期') ?>
    </div>
    <?php if(in_array($playway, [1,2]))?>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_poses')->checkboxList($filter_date_pos1)->label('位置') ?>
    </div>
    <div class="col-lg-2 col-xs-1">
        <?= $form->field($model, 'is_batch_simulate')->checkboxList($is_filter_dates)->label('是否模拟') ?>
    </div>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'test_period_days')->textInput()->label('周期:7, 则模拟最近7天数据') ?>
    </div>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'start_qihao')->textInput()->label('开始期号') ?>
    </div>
    <?php ;?>
</div>