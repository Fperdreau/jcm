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

function getPlugins(page, callback) {
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {
            get_plugins: true,
            page: page
        },
        async: true,
        success: function(data) {
            var json = jQuery.parseJSON(data);
            callback(page, json);
        }
    });
}

function showplugins(page, result) {
    var key;
    for (key in result) {
        var plugin = result[key];
        if (plugin.page == page) {
            console.log(plugin.display);
            $(".plugins")
                .fadeOut(200)
                .append(plugin.display)
                .fadeIn(200);
        }
    }
}

$(document).ready(function() {

    $('.mainbody')

        .on('blur','.plugin_setting', function(e){
            e.preventDefault();
            var input = $(this);
            var option = $(this).attr('data-option');
            var plugin = $(this).attr('data-plugin');
            var value = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    mod_plugins: true,
                    plugin: plugin,
                    option: option,
                    value: value
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json === true) {
                        console.log(json);
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

        .on('change','.plugin_status',function(e) {
            e.preventDefault();
            var input = $(this);
            var plugin = $(this).attr('data-plugin');
            var value = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    plugin_status: true,
                    plugin: plugin,
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

        .on('click','.install_plugin',function(e) {
            e.preventDefault();
            var el = $(this);
            var plugin = $(this).attr('data-plugin');
            var op = $(this).attr('data-op');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    install_plugin: true,
                    plugin: plugin,
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
                        showfeedback("<p id='success'>"+plugin+" successfully "+result+"</p>");
                    } else {
                        showfeedback("<p id='warning'>Oops, something has gone wrong</p>");

                    }
                }

            });
        });
});