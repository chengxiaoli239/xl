<?php
/* @var $form yii\widgets\ActiveForm */
/* @var $model backend\models\UserSysPlans */
/* @var $platformTxtPlayway int */
?>
<div class="row">
    <div class="col-lg-4 col-xs-12">
        <?= $form->field($model, 'use_platform_txt_import')->radioList([0=>'原导入方式', 1=>'盘口 TXT 导入'])->label('本地客户端下注方式') ?>
    </div>
    <div class="col-lg-8 col-xs-12">
        <label for="platform-txt-file">TXT 文件</label>
        <input id="platform-txt-file" type="file" accept=".txt,text/plain" class="form-control">
        <small id="platform-txt-feedback" class="help-block">文件中的每注金额须与计划单注金额一致。</small>
    </div>
</div>
<script>
(function () {
    var playway = <?= (int)$platformTxtPlayway ?>;
    var fileInput = document.getElementById('platform-txt-file');
    var codesInput = document.querySelector('[name="UserSysPlans[import_codes_txts][0]"]');
    var moneyInput = document.getElementById('usersysplans-single');
    var feedback = document.getElementById('platform-txt-feedback');
    if (!fileInput || !codesInput || !moneyInput || !feedback) return;
    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0];
        if (!file) return;
        if (!/\.txt$/i.test(file.name) || file.size > 128 * 1024) {
            feedback.textContent = '请选择不超过 128 KB 的 TXT 文件';
            fileInput.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function () {
            var entries = String(reader.result || '').trim().split(/[\s,，]+/).filter(Boolean);
            var single = Number(moneyInput.value);
            var minimum = playway === 1 ? 1 : 0.1;
            var codes = [];
            if (!entries.length || entries.length > 2500 || !Number.isFinite(single) || single < minimum ||
                (playway === 1 && !Number.isInteger(single))) {
                feedback.textContent = '请检查号码数量和计划单注金额；二定每注须为至少 1 元的整数';
                return;
            }
            for (var i = 0; i < entries.length; i++) {
                var match = /^([0-9Xx]{4})(?:=([0-9]+(?:\.[0-9]+)?))?$/.exec(entries[i]);
                var code = match ? match[1].toUpperCase() : '';
                if (!match || (code.match(/X/g) || []).length !== 3 - playway ||
                    (match[2] && Math.abs(Number(match[2]) - single) > 0.000001)) {
                    feedback.textContent = '号码须与二/三/四定位置一致；文件金额须等于计划单注金额';
                    return;
                }
                codes.push(code);
            }
            codesInput.value = codes.join(' ');
            feedback.textContent = '已载入 ' + codes.length + ' 注，请核对号码后保存';
            var option = document.querySelector('[name="UserSysPlans[use_platform_txt_import]"][value="1"]');
            if (option) option.checked = true;
        };
        reader.onerror = function () { feedback.textContent = '读取 TXT 文件失败'; };
        reader.readAsText(file);
    });
})();
</script>
