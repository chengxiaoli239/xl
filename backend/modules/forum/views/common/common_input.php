<?= $form->field($model, 'plan_type')->radioList($plan_types)->label('计划类型，为"止盈止损"计划时须填以下两项') ?>
<?= $form->field($model, 'take_profits')->textInput($plan_types)->label('止盈点(正数，例：3000)') ?>
<?= $form->field($model, 'stop_loss')->textInput($plan_types)->label('止损点(正数，例：4000)') ?>

<?= $form->field($model, 'tz_sites')->checkboxList($tz_sites_Arr)->label('投注站点') ?>