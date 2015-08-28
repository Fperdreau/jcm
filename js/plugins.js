/**
 * File for javascript/jQuery functions
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Functions required to manage plugins and scheduled tasks
 * @todo: create a plugin instead of series of indepedent functions
 */

/**
 * Get plugins list associated to the current page
 * @param page: current page
 * @param callback: callback function
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

/**
 * Show plugins within the page
 * @param page: current page
 * @param result: array providing plugins list
 */
function showPlugins(page, result) {
    var key;
    for (key in result) {
        var plugin = result[key];
        if (plugin.page == page) {
            $(".plugins")
                .fadeOut(200)
                .append(plugin.display)
                .fadeIn(200);
        }
    }
}

$(document).ready(function() {
    $("<style>")
        .prop("type", "text/css")
        .html("\
            .valid_input {\
                background: rgba(0, 200, 0, .5);\
            }\
            .wrong_input {\
                background: rgba(200, 0, 0, .5);\
            }")
        .appendTo("head");

    $('.mainbody')

    /**
     * Modify plugin/scheduled task settings
     */
        .on('input','.modSettings', function(e){
            e.preventDefault();
            var input = $(this);
            var option = $(this).data('option');
            var name = $(this).data('name');
            var op = $(this).data('op');
            var value = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    modSettings: name,
                    option: option,
                    op: op,
                    value: value
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json === true || json !== false) {
                        if (op == 'cron') {
                            $('#cron_time_'+name).html(json);
                        }
                        input.addClass('valid_input');
                        setTimeout(function(){
                            input.removeClass('valid_input');
                        }, 500)
                    } else {
                        input.addClass('wrong_input');
                        setTimeout(function(){
                            input.removeClass('wrong_input');
                        }, 500)
                    }
                }
            });
        })

    /**
     * Launch installation of plugin/scheduled task
     */
        .on('click','.installDep',function(e) {
            e.preventDefault();
            var el = $(this);
            var name = $(this).attr('data-name');
            var op = $(this).attr('data-op');
            var type = $(this).attr('data-type');
            var div = $(this).closest('.plugDiv');
            var callback = function(result) {
                if (result.status === true) {
                    var newClass = (op=='install') ? 'uninstallBtn':'installBtn';
                    var newattr = (op=='install') ? 'uninstall':'install';
                    $(el)
                        .attr('data-op',newattr)
                        .addClass(newClass);
                }
            };
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    installDep: name,
                    type: type,
                    op: op
                },
                async: true,
                beforeSend: function() {
                    if (op == 'install') {
                        $(el.removeClass('installBtn'));
                    } else {
                        $(el.removeClass('uninstallBtn'));
                    }
                    $(el).addClass('loadBtn');
                },
                success: function(data) {
                    validsubmitform(div,data,callback);
                }

            });
        })

    /**
     * Display plugin/scheduled task options
     */
        .on('click','.optShow',function(e) {
            e.preventDefault();
            var name = $(this).attr('data-name');
            var op = $(this).attr('data-op');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    getOpt: name,
                    op: op
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    $(".plugOpt#"+name)
                        .html(json)
                        .toggle();
                }
            });
        })

    /**
     * Modify plugin/scheduled task options
     */
        .on('click','.modOpt',function(e) {
            e.preventDefault();
            var name = $(this).parent('.plugOpt').attr('id');
            var op = $(this).attr('data-op');
            var div = $(this).closest('.plugDiv');

            // Parse options
            var option = {};
            $(".plugOpt#"+name).find('input').each(function() {
                if ($(this).attr('type') != "submit") {
                    option[$(this).attr('name')] = $(this).val();
                }
            });

            var data = {modOpt: name, op: op, data:option};
            processAjax(div,data);
        })

    /**
     * Run a scheduled task manually
     */
        .on('click','.run_cron',function(e) {
            e.preventDefault();
            var el = $(this);
            var cron = $(this).attr('data-cron');
            var div = $(this).closest('.plugDiv');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    run_cron: true,
                    cron: cron
                },
                async: true,
                beforeSend: function() {
                    $(el).toggleClass('runBtn loadBtn');
                },
                complete: function() {
                    $(el).toggleClass('runBtn loadBtn');
                },
                success: function(data) {
                    validsubmitform(div,data);
                }
            });
        });
});