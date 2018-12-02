/**
 * Created by benson on 6/17/15.
 */
$(document).ready(function () {
    var dd = new Date();
    $(".datepicker").datetimepicker({
        format: "yyyy-mm-dd hh:ii",
        language: "zh-CN",
        todayHighlight: true,
        endDate: dd,
        autoclose: true
    });
});