/*
 Copyright © 2014, Florian Perdreau
 This file is part of Journal Club Manager.

 Journal Club Manager is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Journal Club Manager is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
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
            var el = $(this);
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
                        showfeedback("<p id='success'>"+cron+" successfully "+result+"</p>");
                    } else {
                        showfeedback("<p id='warning'>Oops, something has gone wrong</p>");

                    }
                }
            });
        })

        // Show job options
        .on('click','.optCron',function(e) {
            e.preventDefault();
            var cron = $(this).attr('data-cron');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    getCronOpt: cron
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    $(".jobOpt#"+cron)
                        .html(json)
                        .toggle();
                }
            });
        })

        .on('click','.modCronOpt',function(e) {
            e.preventDefault();
            var cron = $(this).parent('.jobOpt').attr('id');

            // Parse options
            var option = {};
            $(".jobOpt#"+cron).find('input').each(function() {
                if ($(this).attr('type') != "submit") {
                    option[$(this).attr('name')] = $(this).val();
                }
            });

            jQuery.ajax({
                url: "php/form.php",
                async: true,
                type: 'POST',
                data: {modCronOpt: cron, data:option},
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json === true) {
                        showfeedback("<p id='success'>"+cron+"'s settings successfully updated!</p>");
                    } else {
                        showfeedback("<p id='warning'>Oops, something has gone wrong</p>");
                    }
                }
            });


        });


});