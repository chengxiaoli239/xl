<!--动态过滤-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #da4f49;">
    <div class="col-lg-2 col-xs-1">
        <?= $form->field($model, 'is_filter_dynamic')->checkboxList(['1'=>'是'])->label('动态过滤') ?>
    </div>
    <div class="col-lg-10 col-xs-4">
        <?= $form->field($model, 'filter_dynamic_types')->checkboxList(['1'=>'1小1大，剔除前期号码至少2个上奖', 2=>'1小1大，剔除前期号码至少3个上奖', 3=>'头尾去除期号最后两位相加(四定)'])->label('类型') ?>
    </div>
</div>