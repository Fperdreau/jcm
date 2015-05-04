/**
 * Created by Florian on 08/03/2015.
 */

$(document).ready(function() {
    var wrapperleft = $('.cal_wrapper').position().left;
    var wrapperright = wrapperleft + $('.cal_wrapper').outerWidth();
    var corewidth = $('.cal_core').width();

    $('.mainbody')
        .ready(function() {
            $('.cal_day').hide();
            $('.mainbody').find('.cal_day').each(function() {
                    $(this).fadeIn(200);
                });
        })

        .on('click','.cal_nav_btn',function() {
            var op = $(this).attr('id');
            var width = $('.cal_month').first().css('width');
            var firstmonth = $('.cal_month').first().attr('id');
            var lastmonth = $('.cal_month').last().attr('id');
            var exploded = (op == 'prev') ? firstmonth.split('-') : lastmonth.split('-');
            var month_nb = exploded[0].split('_');
            var month = (op == "prev") ? parseInt(month_nb[1])-1:parseInt(month_nb[1])+1;
            var year = exploded[1];
            var monthtoremove = (op == "prev") ? lastmonth:firstmonth;

            var move = (op == "prev") ? "+=":"-=";
            $('.cal_core').animate({
                'left' : move+width
            });

            var coreleft = $('.cal_core').position().left;
            var coreright = coreleft + $('.cal_core').outerWidth();
            var add = coreleft <= wrapperleft || coreright >= wrapperright;
            if (coreleft >= wrapperleft) {
                month = parseInt(month_nb[1])-1;
            } else if (coreright <= wrapperright) {
                month = parseInt(month_nb[1])+1;
            }

            var totalwidth = 0;
            $(".cal_month").each(function() {
               totalwidth += $(this).outerWidth();
            });

            if (add == true) {
                jQuery.ajax({
                    url: 'cal_process.php',
                    type: 'POST',
                    async: true,
                    data: {
                        getmonth: true,
                        month: month,
                        year: year
                    },
                    success: function(data){
                        var result = jQuery.parseJSON(data);
                        if (totalwidth >= corewidth) {
                            $('.cal_month #'+monthtoremove).remove();
                        }

                        if (op === 'prev') {
                            $('.cal_core')
                                .css('left', 0)
                                .prepend(result);
                        } else if (op === 'next') {
                            $('.cal_core')
                                .offset({left: 0})
                                .append(result);
                        }
                    }
                });
            }

        })


});