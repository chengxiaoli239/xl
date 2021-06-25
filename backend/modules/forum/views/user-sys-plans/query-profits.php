<style>
#modalTable #table th{
    width: 16%;
}
#modalTable #table td{
    width: 16%;
}
#modalTable-head{
    height: 40px
}
.table thead > tr > th, .table tbody > tr > th, .table tfoot > tr > th, .table thead > tr > td, .table tbody > tr > td, .table tfoot > tr > td{
    padding: 5px;
}
.table{
    margin-bottom: 1px;
}
</style>

<div id="modalTable" class="modal fade" tabindex="-1" role="dialog" style="display: none;padding-right: -10px;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin:100px auto;">
        <div class="modal-content">
            <div class="modal-header" id="modalTable-head" style="height: 45px;">
                <h5 class="modal-title">查询结果</h5>
                <button type="button" class="close" data-dismiss="modal" style="margin-top: -23px;" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 0px;">
                <div class="bootstrap-table bootstrap4">
                    <div class="fixed-table-toolbar"></div>
                    <div class="fixed-table-container fixed-height" style="padding-bottom: 50px;">
                        <div class="fixed-table-header" style="margin-right: 0px;">
                            <table class="table table-bordered table-hover" style="">
                                <thead style="" id="append_content">
                                <tr class="modal-title" style=""><td><strong>描述</strong></td><td colspan="5" id="modalTable-title"></td></tr>
                                <tr>
                                    <td data-field="id" class="th-inner" style="width: 5px;text-align: center"><strong>#</strong></td>
                                    <td data-field="gamenum" class="th-inner" style="width: 70px;text-align: center;"><strong>时间</strong></td>
                                    <td data-field="water" class="th-inner" style="width: 70px;text-align: center;"><strong>盈亏</strong></td>
                                    <td data-field="summoney" class="th-inner" style="width: 70px;text-align: center;"><strong>中期数</strong></td>
                                    <td data-field="summoney" class="th-inner" style="width: 70px;text-align: center;"><strong>总期数</strong></td>
                                    <td data-field="okmoney" class="th-inner" style="width: 70px;text-align: center;"><strong>总组数</strong></td>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="fixed-table-footer" style="display: none;">
                            <table><thead><tr></tr></thead></table>
                        </div>
                    </div>
                    <div class="fixed-table-pagination" style="display: none;"></div>
                </div><div class="clearfix"></div>
            </div>
            <div class="modal-footer">
                <!--
                <button type="button" class="btn btn-secondary act-data-type act-data-type-0" data-type="0">今日</button>
                <button type="button" class="btn btn-secondary act-data-type act-data-type-1" data-type="1">昨日</button>
                <button type="button" class="btn btn-secondary act-data-type act-data-type-2" data-type="2">前天</button>
                -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<script src="/statics/datetimepicker/jquery.js"></script>
<script>
// 利润查询 - 月
$(".id-query-profits").click(function () {
    url = '/forum/ssc-static-yl/query-profits'
    data = $('#w0').serialize()+'&static_type='+$(this).data('static-type');
    $.post(url, data, function(rst) {
        console.log(rst);
        table_str = '';
        datas = rst.datas
        for (i in datas){
            k = parseInt(i) + 1;
            tmpData = datas[i];
            console.log('time', tmpData['timer'])
            console.log('timer', tmpData.timer)
            //isj = isJSON(tmpData)
            //console.log(isj)
            table_str += '<tr data-index="1">'+
            '<td class="th-inner" style="width: 10px;text-align: center;"><font color="green">'+k+'</font></td>'+ //
            '<td class="th-inner" style="width: 75px;text-align: center;"><font color="gray">'+tmpData['time']+'</font></td>'+ // 月份、年份
            '<td class="th-inner" style="width: 70px;text-align: center;"><font color="gray">'+tmpData['profits']+'</font></td>'+ // 利润
            '<td class="th-inner" style="width: 70px;text-align: center;"><font color="gray">'+tmpData['zj_qishus']+'</font></td>'+ // 中期数
            '<td class="th-inner" style="width: 70px;text-align: center;"><font color="gray">'+tmpData['all_qishus']+'</font></td>'+ // 总期数
            '<td class="th-inner" style="width: 70px;text-align: center;"><font color="gray">'+tmpData['counts']+'</font></td>'+ // 总组数
            //'<td class="th-inner" style="width: 70px;"><font color="gray">55555</font></td>'+ //
            '</tr>';
        }
        $("#append_content").append(table_str)
        //$("#tbody-content").html(table_str);
        console.log(rst.code_desc);
        $('#modalTable-title').html("<strong>" + rst.code_desc + "</strong>");
        $('#modalTable').modal('show');
    });
});
function isJSON(str) {
    if (typeof str == 'string') {
        try {
            var obj=JSON.parse(str);
            if(typeof obj == 'object' && obj ){
                return true;
            }else{
                return false;
            }

        } catch(e) {
            console.log('error：'+str+'!!!'+e);
            return false;
        }
    }
    console.log('It is not a string!')
}

</script>
