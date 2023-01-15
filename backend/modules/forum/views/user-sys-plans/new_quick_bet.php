<style>
    .form-control{
        padding: 0px 3px;
    }
</style>
<!-- 任意位置(不定位)-99-->
<?php include(dirname(__FILE__).'/filters/ever_positions.php'); # 任意位置(不定位)-99 ?>

<?php $turn_key = \Yii::$app->params['IMPORT_CODES_TURN']; for($i=1; $i<$turn_key; $i++){?>
<div class="row import_codes_txts <?if (!$model->change_per) echo 'hide';?>">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model,"import_codes_txts[$i]")->textarea([ 'autofocus' => false,'style'=>'height:150px' ])->label('号码'.$i.'：多组英文逗号或空格隔开 2345,3456 或 2345 3456');?>
    </div>
</div>
<?}?>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>

</script>