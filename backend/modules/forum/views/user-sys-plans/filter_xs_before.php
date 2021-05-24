<!--过滤前x期的号码，或者前一个区间内的号码-->
<div class="row" style="border-width:1px;border-style:solid;border-color: green;">
    <div class="col-lg-1 col-xs-1">
        <?= $form->field($model, 'is_filter')->checkboxList($is_filters)->label('排除') ?>
    </div>
    <div class="col-lg-5 col-xs-5">
        <?= $form->field($model, 'filter_xQ_before')->textInput()->label('例如：1,2;4~6 则排前1，2，4，5，6期') ?>
    </div>
    <?php if(in_array($playway, [1,2]))?>
    <div class="col-lg-3 col-xs-3">
        <?= $form->field($model, 'filter_pos1')->checkboxList($filter_pos1)->label('位置1(二定:勾选位要跟导入码一致或相反)') ?>
    </div>
    <?php ;?>
    <?php if(in_array($playway, [1]))?>
    <div class="col-lg-3 col-xs-3">
        <?= $form->field($model, 'filter_pos2')->checkboxList($filter_pos2)->label('位置2(二定:勾选位要跟导入码一致或相反)') ?>
    </div>
    <?php ;?>
</div>
