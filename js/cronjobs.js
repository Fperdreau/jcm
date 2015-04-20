/**
 * Created by florian on 07/04/15.
 */

/**
 * Created by Florian on 30/03/2015.
 */

function getcrons(page, callback) {
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {
            get_jobs: true,
            page: page
        },
        async: true,
        success: function(data) {
            var json = jQuery.parseJSON(data);
            callback(page, json);
        }
    });
}


$(document).ready(function() {

    $('.mainbody')

        .on('change','.cron_setting', function(e){
            e.preventDefault();
            var input = $(this);
            var option = $(this).attr('data-setting');
            var cron = $(this).attr('data-cron');
            var value = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    mod_cron: true,
                    cron: cron,
                    option: option,
                    value: value
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json !== false) {
                        console.log(json);
                        $('#cron_time_'+cron).html(json);
                    } else {
                        input
                            .addClass('wrong_input');
                        setTimeout(function(){
                            input.removeClass('wrong_input');
                        }, 500)
                    }
                }
            });
        })

        .on('change','.cron_status',function(e) {
            e.preventDefault();
            var input = $(this);
            var cron = $(this).attr('data-cron');
            var value = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    cron_status: true,
                    cron: cron,
                    status: value
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json === true) {
                        input
                            .addClass('valid_input');
                        setTimeout(function(){
                            input.removeClass('valid_input');
                        }, 500)
                    } else {
                        input
                            .addClass('wrong_input');
                        setTimeout(function(){
                            input.removeClass('wrong_input');
                        }, 500)
                    }
                }
            });
        })

        .on('click','.run_cron',function(e) {
            e.preventDefault();
            var el = $(this);
            var cron = $(this).attr('data-cron');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    run_cron: true,
                    cron: cron
                },
                async: true,
                beforeSend: function() {
                    $(el).html('<div style="text-align: center; padding: 0; margin: 0;"><img src="images/36.gif" width="70%" style="vertical-align: middle"></div>');
                },
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    $(el).html('Run');
                    showfeedback("<p id='status'>"+json+"</p>");
                }
            });
        })

        .on('click','.install_cron',function(e) {
            e.preventDefault();
            var el = $(this)
            var cron = $(this).attr('data-cron');
            var op = $(this).attr('data-op');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    install_cron: true,
                    cron: cron,
                    op: op
                },
                async: true,
                beforeSend: function() {
                    $(el).html('<div style="text-align: center; padding: 0; margin: 0;"><img src="images/36.gif" width="70%"></div>');
                },
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    var result = (op=='install') ? 'installed':'uninstalled';
                    if (json === true) {
                        var newcontent = (op=='install') ? 'Uninstall':'Install';
                        var newattr = (op=='install') ? 'uninstall':'install';
                        $(el)
                            .attr('data-op',newattr)
                            .html(newcontent);
                        showfeedback("<p id='success'>cron successfully "+result+"</p>");
                    } else {
                        showfeedback("<p id='warning'>Oops, something has gone wrong</p>");

                    }
                }
            });
        });
});