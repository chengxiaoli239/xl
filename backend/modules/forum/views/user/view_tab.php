<?php

use yii\helpers\Html;

if(count($userTypes)>1)
foreach ($userTypes as $userType) {
    $class = $userType['user_type'] == $user_type ? 'btn-success' : 'btn-default';
?>
<div class="btn-group">
    <?= Html::a($userType['name'], ['view', 'TzSystemsUsers[user_type]' => $userType['user_type']], ['class' => 'btn '.$class, 'style' => 'margin-bottom:15px;']) ?>
</div>
<?php
}
?>

