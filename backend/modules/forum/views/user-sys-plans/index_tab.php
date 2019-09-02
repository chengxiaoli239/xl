<?php

use yii\helpers\Html;

foreach ($lottery_types as $lottery_typee) {
?>
<div class="btn-group">
    <?= Html::a($lottery_typee['name'], ['index', 'UserSysPlans[lottery_type]' => $lottery_typee['lottery_type']], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
</div>
<?php
}
?>

