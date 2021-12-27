<!--过滤前x期的号码，或者前一个区间内的号码-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: green;">
    <div class="col-lg-1 col-xs-1">
        <?= $form->field($model, 'is_filter')->checkboxList($is_filters)->label('排除') ?>
    </div>
    <div class="col-lg-5 col-xs-5">
        <?= $form->field($model, 'filter_xQ_before')->textInput()->label('例：1,2;4~6 则排前1，2，4，5，6期') ?>
    </div>
    <?php if(in_array($playway, [1,2]))?>
    <div class="col-lg-3 col-xs-3">
        <?= $form->field($model, 'filter_pos1')->checkboxList($filter_pos1)->label('位置1') ?>
    </div>
    <?php ;?>
    <?php if(in_array($playway, [1]))?>
    <div class="col-lg-3 col-xs-3">
        <?= $form->field($model, 'filter_pos2')->checkboxList($filter_pos2)->label('位置2') ?>
    </div>
    <?php ;?>
</div>

<!--排除前X天内同期号码-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #7000cb;">
    <div class="col-lg-1 col-xs-1">
        <?= $form->field($model, 'is_filter_date')->checkboxList($is_filter_dates)->label('排除X天同期') ?>
    </div>
    <div class="col-lg-5 col-xs-5">
        <?= $form->field($model, 'filter_xD_before')->textInput()->label('例：1,2;4~6 则排前1，2，4，5，6天') ?>
    </div>
    <?php if(in_array($playway, [1,2]))?>
    <div class="col-lg-3 col-xs-3">
        <?= $form->field($model, 'filter_date_pos1')->checkboxList($filter_date_pos1)->label('位置1') ?>
    </div>
    <?php ;?>
    <?php if(in_array($playway, [1]))?>
    <div class="col-lg-3 col-xs-3">
        <?= $form->field($model, 'filter_date_pos2')->checkboxList($filter_date_pos2)->label('位置2') ?>
    </div>
    <?php ;?>
</div>

<!--排除期号-->
<!--
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: green;">
    <div class="col-lg-1 col-xs-1">
        <?= $form->field($model, 'is_filter_qihao')->checkboxList($is_filter_qihaos)->label('排除期号') ?>
    </div>
</div>
-->

<!--每期动态过滤-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #7000cb;">
    <div class="col-lg-2 col-xs-1">
        <?= $form->field($model, 'is_batch_simulate')->checkboxList($is_filter_dates)->label('是否模拟') ?>
    </div>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_type')->dropDownList([''=>'请选择', 1=>'同位置前x期'])->label('排除类型') ?>
    </div>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_nums')->textInput()->label('例:2 则排前2期') ?>
    </div>
    <?php if(in_array($playway, [1,2]))?>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_poses')->checkboxList($filter_date_pos1)->label('位置') ?>
    </div>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'start_qihao')->textInput()->label('开始期号') ?>
    </div>
    <?php ;?>
</div>