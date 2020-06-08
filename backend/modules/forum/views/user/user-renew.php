<!--修改结果提示-->
<div class="modal fade in" id="rstTipModalCm" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title_cm">提示信息</h4>
            </div>
            <div class="modal-body" id="modal-body-cm">
                <div class="form-group up-reason" id="append-content-cm">
                    <label id="tip_msg_rst_cm" for="tip_msg_rst_cm"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="ApplyCmConfirm">确定</button>
            </div>
        </div>
    </div>
</div>
<!--2、提佣 -->
<span id="edit_cm" style="display: none;">
    <div class="form-group">
        <label for="points" class="col-sm-2 control-label" style="margin-top:6px">到期时间</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="user_cm" name="user_cm" placeholder="请输入时间" value="">
            <span>输入到期时间</span>
        </div>
        <div class="clearfix"></div>
    </div>
</span>
<!--修改结果提示-->
<div class="modal fade in" id="rstTipModalEndCm" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_title_end_cm">提示信息</h4>
            </div>
            <div class="modal-body" id="modal-body-cm">
                <div class="form-group up-reason">
                    <label id="tip_msg_title_end_cm" for="tip_msg_title_end_cm"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="ApplyCmConfirmEnd">确定</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        // 修改过期时间
        $('.renew-account').click(function () {
            var up_name = $(this).attr('data-name');

            op_id = $(this).attr('id');
            $("#edit_cm").show();
            $("#modal-body-cm").html($('#edit_cm'))
            $('#ApplyCmConfirm').attr('data-op-id', op_id);
            showTips('过期时间', '输入到期日期：');
        });
        /**
         * @desc 显示修改结果提示框
         * @param tip_msg
         * @param title
         */
        function showTips(tip_msg = '信息变动', title = '提示信息') {
            $('#tip_msg_title_cm').html(title);
            $('#tip_msg_rst_cm').html(tip_msg);
            $('#rstTipModalCm').modal('show');
        }

        $("#ApplyCmConfirm").click(function () {
            money = $('#user_cm').val();
            applyCommission(money);
        });

        /**
         * @desc 接口
         * @param uid
         * @param pwd
         */
        function applyCommission(money) {
            data = {money:money};
            $.post("/users/proxygetcommissionrec/apply-get",data,function(rst) {
                Qrst = rst.data;
                status = rst.status;
                msg = '';
                if(rst.status == 200){
                    username = Qrst.username;
                    msg = '提佣：'+money+'，旧：'+ Qrst.old_cm + '，新：' + Qrst.new_cm.toFixed(2) + '，' + rst.msg;
                    $("#ApplyCmConfirmEnd").attr('refresh', 1);
                }else {
                    $("#ApplyCmConfirmEnd").attr('refresh', 0);
                    msg = rst.msg;
                }

                $("#tip_msg_title_end_cm").html(msg);
                $("#rstTipModalEndCm").modal('show');
            },'JSON');
        }

        /**
         * 确定按钮
         */
        $("#ApplyCmConfirmEnd").click(function () {
            refresh = $(this).attr('refresh');
            if(refresh == 1){
                url = location.href.replace(/#/g, '');
                window.location.href = url;
            }
        });
    });
</script>
