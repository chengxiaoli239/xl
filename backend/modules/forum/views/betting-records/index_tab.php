<?php

use yii\helpers\Html;

foreach ($lottery_types as $lottery_type) {
?>
<div class="btn-group">
    <?= Html::a($lottery_type['name'], ['index', 'BettingRecords[lottery_type]' => $lottery_type['lottery_type']], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
</div>
<?php
}
?>

