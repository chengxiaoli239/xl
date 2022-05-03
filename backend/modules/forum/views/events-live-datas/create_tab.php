<?php

use yii\helpers\Html;

if(count($sport_types)>1)
foreach ($sport_types as $s_type) {
    $class = ($s_type['sport_type'] == $sport_type) ? 'btn-success' : 'btn-default';
?>
<div class="btn-group">
    <?= Html::a(str_replace(['体育'],'',$s_type['name']), ['game-related', 'SportType[sport_type]' => $s_type['sport_type']], ['class' => 'btn '.$class, 'style' => 'margin-bottom:15px;']) ?>
</div>
<?php
}
?>

