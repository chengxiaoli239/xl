<!--每期动态过滤-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #da4f49;">
    <div class="col-lg-2 col-xs-1">
        <?= $form->field($model, 'is_filter_history')->checkboxList($is_filters)->label('是否过滤') ?>
    </div>
    <div class="col-lg-2 col-xs-4">
        <?= $form->field($model, 'filter_history_nums')->textInput()->label('前多少个号码数据') ?>
    </div>
</div>