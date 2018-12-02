/**
 * Created by benson on 6/17/15.
 */
$(document).ready(function () {
    var dd = new Date();

     $(".datepicker").each(function(){

        var config = {
                format: "yyyy-mm-dd hh:ii",
                language: "zh-CN",
                todayHighlight: true,
                endDate: dd,
                autoclose: true
            };
        var obj = $(this);
        var format = obj.data('date-format');
        if (format) {
            config['format'] = format;
        }
        var view = obj.data('date-view');
        switch(view){
            case 'day':
                config['minView']= 2;
                break;
        }
        obj.datetimepicker(config);

     });
});