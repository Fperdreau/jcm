/**
 * Created by Florian on 16/04/2015.
 */

$(document).ready(function() {

    $('mainbody')
        .on('click','.addmenu',function(e) {
            $(this).find('.submenu').css('visibility', 'visible');
        })
});