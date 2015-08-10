/**
 * Created by Florian on 08/03/2015.
 */

var today = new Date();
var firstMonth = today.getMonth();
var year = today.getYear();

function getCalendar(monthToShow, month, year) {

    jQuery.ajax({
        url: 'cal_process.php',
        type: 'POST',
        async: true,
        data: {
            showCal: monthToShow,
            month: month,
            year: year
        },
        success: function(data){
            var result = jQuery.parseJSON(data);
            console.log(result);
            $('#content').html(result);
        }
    });
}

function updateCalendar(monthToShow, month, year) {

    jQuery.ajax({
        url: 'cal_process.php',
        type: 'POST',
        async: true,
        data: {
            getMonth: monthToShow,
            month: month,
            year: year
        },
        success: function(data){
            var result = jQuery.parseJSON(data);
            animateCal(op, result);
        }
    });
}

function animateCal(op, result) {
    var move = (op == "prev") ? "+=":"-=";
    var wrapperleft = $('.cal_wrapper').position().left;
    var wrapperright = wrapperleft + $('.cal_wrapper').outerWidth();
    var corewidth = $('.cal_core').width();

    if (totalwidth >= corewidth) {
        $('.cal_month #'+monthtoremove).remove();
    }

    if (op === 'prev') {
        $('.cal_core')
            .css('left', 0)
            .prepend(result)
            .animate({
                'left' : move+width
            });
    } else if (op === 'next') {
        $('.cal_core')
            .offset({left: 0})
            .append(result)
            .animate({
                'left' : move+width
            });
    }
}

$(document).ready(function() {

    var windowsWidth = $(document).width();
    var nbMonthToShow = Math.round(windowsWidth/300);
    getCalendar(nbMonthToShow,firstMonth,year);

    $('.mainbody')

        .on('click','.cal_nav_btn',function() {
            var op = $(this).attr('id');
            var width = $('.cal_month').first().css('width');
            var firstmonth = $('.cal_month').first().attr('id');
            var exploded = (op == 'prev') ? firstmonth.split('-') : lastmonth.split('-');
            var month_nb = exploded[0].split('_');
            var month = (op == "prev") ? parseInt(month_nb[1])-1:parseInt(month_nb[1])+1;
            var year = exploded[1];

            var coreleft = $('.cal_core').position().left;
            var coreright = coreleft + $('.cal_core').outerWidth();

            if (coreleft >= wrapperleft) {
                month = parseInt(month_nb[1])-1;
            } else if (coreright <= wrapperright) {
                month = parseInt(month_nb[1])+1;
            }

            var totalwidth = 0;
            $(".cal_month").each(function() {
               totalwidth += $(this).outerWidth();
            });

            updateCalendar(nbMonthToShow,month,year);

        });


});