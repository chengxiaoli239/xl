<!--每期动态过滤-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #7000cb;">
    <!--
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_nums')->textInput()->label('例:2 则排前2期') ?>
    </div>
    <?php if(in_array($playway, [1,2]))?>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_poses')->checkboxList($filter_date_pos1)->label('位置') ?>
    </div>
    -->
    <div class="col-lg-2 col-xs-5">
        <?= $form->field($model, 'is_batch_simulate')->checkboxList($is_filter_dates)->label('历史回测（不自动追期）') ?>
    </div>
    <!--
    <div class="col-lg-2 col-xs-7">
        <?= $form->field($model, 'test_period_days')->textInput()->label('周期:7则模拟最近7天数据') ?>
    </div>
    -->
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'start_qihao')->textInput()->label('历史回测开始期号') ?>
    </div>
    <!--
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'filter_type')->dropDownList($code_filter_types)->label('排除类型') ?>
    </div>
    -->
    <?php ;?>
</div>
