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
.table thead > tr > td, .table tbody > tr > td{
    //padding: 6px;
}
.table{
    margin-bottom: 1px;
}
</style>

<div id="modalTable" class="modal fade" tabindex="-1" role="dialog" style="display: none;padding-right: -10px;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin:100px auto;">
        <div class="modal-content">
            <div class="modal-header" id="modalTable-head" style="height: 45px;">
                <h5 class="modal-title" style="" id="modalTable-title">号码：全部</h5>
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
                                <thead style="">
                                <tr>
                                    <td data-field="id" class="th-inner" style="width: 10px;">#</td>
                                    <td data-field="gamenum" class="th-inner" style="width: 75px;">时间</td>
                                    <td data-field="water" class="th-inner" style="width: 70px;">盈亏</td>
                                    <td data-field="summoney" class="th-inner" style="width: 70px;">中期数</td>
                                    <td data-field="summoney" class="th-inner" style="width: 70px;">总期数</td>
                                    <td data-field="okmoney" class="th-inner" style="width: 70px;">总组数</td>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="fixed-table-body">
                            <div class="fixed-table-loading table table-bordered table-hover fixed-table-border" style="display: none; width: 100%;">
                              <span class="loading-wrap">
                                  <span class="loading-text">Loading, please wait</span>
                                  <span class="animation-wrap"><span class="animation-dot"></span></span>
                              </span>
                            </div>
                            <table id="table" data-toggle="bootstrap-table" data-height="499" data-url="json/data1.json" class="table table-bordered table-hover" style="margin-top: -3px;">
                                <tbody id="tbody-content">
                                <!--
                                <tr data-index="9"><td style="">9</td><td style="width:16px;">Item 0</td><td style="">$0</td><td style="">0</td><td style="">Item 0</td><td style="">$0</td></tr>
                                -->
                                </tbody>
                            </table>
                            <div class="fixed-table-border" style="height: 0px;"></div>
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
<script>
// 利润查询 - 月
$(".id-query-profits").click(function () {
    url = '/forum/ssc-static-yl/query-profits'
    console.log('askldjfjk');
    data = $('#w0').serialize()+'&static_type='+$(this).data('static-type');
    $.post(url, data, function(rst) {
        console.log(rst);
        table_str = '';
        //$('#tip_msg_rst').html('<strong>号码：</strong>'+rst.code_desc + "<br>" +'<strong>组数：</strong>'+ rst.counts + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
        datas = rst.datas
        for (i in datas){
            k = parseInt(i) + 1;
            tmpData = datas[i];
            console.log('time', tmpData['timer'])
            console.log('timer', tmpData.timer)
            //isj = isJSON(tmpData)
            //console.log(isj)
            table_str += '<tr data-index="1">'+
            '<td class="th-inner" style="width: 10px;"><font color="green">'+k+'</font></td>'+ //
            '<td class="th-inner" style="width: 75px;"><font color="gray">'+tmpData['time']+'</font></td>'+ // 月份、年份
            '<td class="th-inner" style="width: 70px;"><font color="gray">'+tmpData['profits']+'</font></td>'+ // 利润
            '<td class="th-inner" style="width: 70px;"><font color="gray">'+tmpData['zj_qishus']+'</font></td>'+ // 中期数
            '<td class="th-inner" style="width: 70px;"><font color="gray">'+tmpData['all_qishus']+'</font></td>'+ // 总期数
            '<td class="th-inner" style="width: 70px;"><font color="gray">'+tmpData['counts']+'</font></td>'+ // 总组数
            //'<td class="th-inner" style="width: 70px;"><font color="gray">55555</font></td>'+ //
            '</tr>';
        }
        $("#tbody-content").html(table_str);
        console.log(rst.code_desc);
        $('#modalTable-title').html("号码：" + rst.code_desc);
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
