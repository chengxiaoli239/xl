<?php

use yii\helpers\Html;

if(count($lottery_types)>1)
foreach ($lottery_types as $k=>$lottery) {
    $class = $lottery['lottery_type'] == $lottery_type ? 'btn-success' : 'btn-default';
?>
<div class="btn-group">
    <?= Html::a(str_replace([''],'',$lottery['name']), ['index', 'UserSysPlans[lottery_type]' => $lottery['lottery_type']], ['class' => 'btn btn-sm '.$class, 'style' => 'margin-bottom:15px;']) ?>
</div>
<?php
}
?>

<!--微信二维码模态框-->
<!--
<div class="modal fade" id="wechatQrModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title">客服微信二维码</h4>
            </div>
            <div class="modal-body text-center">
                <div class="form-group">
                    <img src="/statics/img/service.jpg" alt="微信二维码" class="img-responsive" style="max-width: 200px; margin: 0 auto;" id="wechatQrImage">
                </div>
                <p class="text-muted">微信号：TedGod</p>
                <p class="text-muted small">长按二维码保存到相册</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" id="saveQrCode">保存二维码</button>
            </div>
        </div>
    </div>
</div>
-->

<script>
$(function () {
    // 微信二维码查看
    $('#viewWechatQr').click(function () {
        $('#wechatQrModal').modal('show');
    });

    // 保存二维码功能
    $('#saveQrCode').click(function () {
        var img = document.getElementById('wechatQrImage');
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');

        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;

        ctx.drawImage(img, 0, 0);

        canvas.toBlob(function(blob) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'wechat_qr_tedgod.jpg';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 'image/jpeg', 0.9);
    });

    // 长按保存功能（移动端）
    $('#wechatQrImage').on('contextmenu', function(e) {
        e.preventDefault();
        // 在移动端，长按会触发contextmenu事件
        // 这里可以添加提示信息
        alert('请长按图片选择保存到相册');
    });
});
</script>

