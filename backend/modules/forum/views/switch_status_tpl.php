<!--提示框-start-->
<div class="modal fade " id="switch_Modal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="switch_rst_msg">信息提示：</h4>
            </div>
            <div class="modal-body">
                <span id="switch_tip_msg"></span>
            </div>
            <!--div class="form-group down-reason">
                <p><label>备注信息:</label><input class="form-control" id="message" name="message" /></p>
            </div-->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<!--提示框-end-->
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
$(function () {
    $(".act-switch-status").click(function (rst) {
        model = $(this).data('act-model')
        field = $(this).data('act-field')
        id = $(this).data('act-id')
        data = {id:id, model:model, field:field}
        url = '/forum/common-set/switch-status'
        $.post(url, data, function(rst) {
            $('#switch_tip_msg').text(rst.msg)

            $('#switch_Modal_msg').modal('show');
        });
    });
    $("#confirm_ms").click(function(){
        location.reload();
    });
});
</script>
