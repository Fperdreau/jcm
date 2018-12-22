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
 * Show plugins within the page
 * @param page: current page
 * @param result: array providing plugins list
 */
function showPlugins(page, result) {
    var key;
    var page_id = page.split('/');

    for (key in result) {
        if (result.hasOwnProperty(key)) {
            var plugin = result[key];
            if (plugin.page === page_id[page_id.length-1]) {
                $(".plugins")
                    .fadeOut(200)
                    .append("<section class='plugin' id='" + key + "'>"+plugin.display+"</section>")
                    .fadeIn(200);
            }
        }
    }
}

/**
 * Execute action via AJAX call
 * @param el: node
 * @param states
 */
function execute(el, states) {
    var name = el.data('name');
    var controller = el.data('controller');
    var action = el.attr('data-action');
    var div = el.closest('.plugDiv');
    var curClass = action + 'Btn';
    var idx = states.findIndex(k => k!=action);
    var newAttr = states[idx];
    var newClass = newAttr + 'Btn';

    jQuery.ajax({
        url: 'php/router.php?controller=' + controller + '&action=' + action,
        type: 'POST',
        data: {
            name: name
        },
        async: true,
        beforeSend: function() {
            $(el).removeClass(curClass);
            $(el).addClass('loadBtn');
        },
        success: function(data) {
            validsubmitform(div, jQuery.parseJSON(data), function(result) {
                if (result.status === true) {
                    $(el)
                        .attr('data-action', newAttr)
                        .removeClass('loadBtn')
                        .addClass(newClass);
                } else {
                    $(el).removeClass('loadBtn');
                }
            });
        }

    });
}

/**
 * Execute scheduled task
 *
 * @param {*} el: DOM element (button)
 */
function runTask(el) {
    var cron = el.attr('data-cron');
    var div = el.closest('.plugDiv');
    jQuery.ajax({
        url: 'php/router.php?controller=Tasks&action=execute&name=' + cron,
        type: 'POST',
        data: {
            name: cron
        },
        async: true,
        beforeSend: function() {
            $(el).toggleClass('runBtn loadBtn');
        },
        complete: function() {
            $(el).toggleClass('loadBtn runBtn');
        },
        success: function(data) {
            validsubmitform(div, jQuery.parseJSON(data));
        }
    });
}

function stopTask(el) {
    var cron = el.attr('data-cron');
    var div = el.closest('.plugDiv');

    jQuery.ajax({
        url: 'php/router.php?controller=Tasks&action=stop&name=' + cron,
        type: 'POST',
        data: {
            name: cron
        },
        async: true,
        beforeSend: function() {
            $(el).toggleClass('runBtn loadBtn');
        },
        complete: function() {
            $(el).toggleClass('loadBtn runBtn');
        },
        success: function(data) {
            div.find('.task_running_icon').toggleClass('running not_running');
            validsubmitform(div, data);

        }
    });
}

$(document).ready(function() {
    $("<style>")
        .prop("type", "text/styles")
        .html("\
            .valid_input {\
                background: rgba(0, 200, 0, .5);\
            }\
            .wrong_input {\
                background: rgba(200, 0, 0, .5);\
            }")
        .appendTo("head");

    $(document)

    /**
     * Modify plugin/scheduled task settings
     */
        .on('click', '.modCron', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            processForm(form, function(json) {
                if (json.status) {
                    $('#cron_time_' + form.find('input[name="name"]').val()).html(json.msg).fadeIn(200);
                }
            });
        })

        /**
         * Install plugin/scheduled task
         */
        .on('click','.installDep',function(e) {
            e.preventDefault();
            execute($(this), ['install', 'uninstall']);
        })

        /**
         * Activate/Deactivate plugin/scheduled task
         */
        .on('click','.activateDep', function(e) {
            e.preventDefault();
            execute($(this), ['activate', 'deactivate']);
        })

        /**
         * Show task's logs
         */
        .on('click', '.showLog', function(e) {
            e.preventDefault();
            showLogs($(this));
        })

        .on('click', '.deleteLog', function(e) {
            e.preventDefault();
            deleteLogs($(this));
        })

        /**
         * Run a scheduled task manually
         */
        .on('click','.run_cron',function(e) {
            e.preventDefault();
            runTask($(this));
        })

        /**
         * Stop a scheduled task manually
         */
        .on('click','.stop_cron',function(e) {
            e.preventDefault();
            stopTask($(this));
        });
});
