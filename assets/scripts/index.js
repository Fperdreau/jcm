/**
 * File for js and jquery functions
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

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 GET FORMS
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
/**
 * Show form to submit a presentation
 * @param data
 */
var get_submission_form = function (data) {
    if (data['id'] === undefined) {data['id'] = false; }
    if (data['operation'] === undefined) {data['operation'] = "new"; }
    if (data['date'] === undefined) {data['date'] = false; }
    if (data['type'] === undefined) {data['type'] = false; }
    if (data['view'] === undefined) data['view'] = 'body';
    data['loadContent'] = true;
    var el = (data['destination'] === undefined) ? $('.submission_container') : $(data['destination']);

    // First we remove any existing submission form
    var callback = function (result) {
        el
            .html(result)
            .fadeIn(200)
            .find('textarea').html(result.content);

        loadWYSIWYGEditor();

        // Load JCM calendar
        loadCalendarSubmission();
    };

    processAjax(el, data, callback, "php/form.php");
};

/**
 * Load content into target selector
 * @param el: current selector
 * @param {undefined|function} final_callback
 */
function loadContent(el, final_callback) {
    var data = el.data();
    data.loadContent = true;

    var destination = (data.destination === undefined) ? el.closest('section') : $(data['destination']);

    // Get target url
    var url;
    if (el.data('url') !== undefined) {
        url = el.data('url');
    } else if (el.attr('href') !== undefined) {
        url = el.attr('href');
    } else {
        url = 'php/form.php';
    }

    var callback = function (result) {
        var html = result.content === undefined ? result : result.content;
        destination
            .html(html)
            .css('visibility', 'visible')
            .fadeIn(200);

        // Load WYSIWYG editor
        loadWYSIWYGEditor();

        // Load JCM calendar
        loadCalendarSubmission();

        if (final_callback !== undefined) {
            final_callback(result);
        }
    };

    processAjax(destination, data, callback, url);
}

/**
 * Execute action on select input and load result in target div
 * 
 * @param el: current selector
 * @param {undefined|function} final_callback
 */
function actionOnSelect(el, final_callback) {
    var form = el.length > 0 ? $(el[0].form) : $();
    var data = getData(form);

    var destination = (el.data('destination') === undefined) ? el.closest('section') : $(el.data('destination'));

    // Get target url
    var url = form.attr('action');

    var callback = function (result) {
        if (result.content !== undefined) {
            var html = result.content === undefined ? result : result.content;
            destination
                .html(html)
                .css('visibility', 'visible')
                .fadeIn(200);
    
            // Load WYSIWYG editor
            loadWYSIWYGEditor();
    
            // Load JCM calendar
            loadCalendarSubmission();
    
            if (final_callback !== undefined) {
                final_callback(result);
            }
        }

    };

    processAjax(destination, data, callback, url);
}

/**
 * Display form to post a news
 * @param postid
 */
var showpostform = function (postid) {
    var el = $('.post_edit_container');
    var data = {post_show: true, postid: postid};
    var callback = function (result) {
        var text_area = "<textarea name='content' id='post_edit_container' class='wygiwgm'>" + result.content + "</textarea>";
        el
            .empty()
            .html(result.content)
            .fadeIn(200);
        $('.post_txtarea')
            .html(text_area)
            .show();

        loadWYSIWYGEditor();
    };
    processAjax(el, data, callback, "php/router.php?controller=Posts&action=editor");
};

/**
 * Render a confirmation box
 * @param el: clicked element
 * @param txt: message to display in dialog box
 * @param txt_btn: text of confirmation button
 * @param callback: callback function (called if user has confirmed)
 */
function confirmationBox(el, txt, txt_btn, callback) {
    trigger_modal(el, false);

    var container = $('.modalContainer');

    // Remove confirmation section if it already exist
    if (container.find('.modal_section#confirmation_box').length > 0) {
        container.find('.modal_section#confirmation_box').remove();
    }

    // Render section
    jQuery.ajax({
        'type': 'post',
        'url': 'php/router.php?controller=Modal&action=getBox&type=confirmation',
        'data': {
            button_txt: txt_btn,
            text: txt
        },
        async: true,
        success: function(json) {
            var result = jQuery.parseJSON(json);

            container.find('.popupBody').append(result);

            // Show  section
            el.modalTrigger('showOverlay');

            var modal = el.modalTrigger('getWindow');

            modal.modalWindow('show_section', 'confirmation_box');

            var section = $('.modal_section#confirmation_box');

            // User has confirmed
            section.find("input[name='confirmation']").click(function() {
                if (callback !== undefined) {
                    callback();
                }
            });

            // User cancelled
            section.find("input[name='cancel']").click(function() {
                modal.modalWindow('close');
            });
        }
    });
}

/**
 * Render a confirmation box
 * @param el: clicked element
 * @param txt: message to display in dialog box
 * @param title: dialog title
 * @param callback: callback function (optional)
 */
function dialogBox(el, txt, title, callback) {
    trigger_modal(el, false);

    if (title === undefined) {
        title = '';
    }

    var container = $('.modalContainer');

    // Remove confirmation section if it already exist
    if (container.find('.modal_section#confirmationBox').length > 0) {
        container.find('.modal_section#confirmationBox').remove();
    }

    // Render section
    jQuery.ajax({
        'type': 'post',
        'url': 'php/router.php?controller=Modal&action=getBox&type=dialog',
        'data': {
            text: txt,
            title: title
        },
        async: true,
        success: function(json) {
            var result = jQuery.parseJSON(json);

            container.find('.popupBody').append(result);

            // Show  section
            el.modalTrigger('showOverlay');

            var modal = el.modalTrigger('getWindow');

            modal.modalWindow('show_section', 'confirmationBox');

            var section = $('.modal_section#confirmationBox');

            // User has confirmed
            section.find(".callback_trigger").click(function() {
                callback($(this));
            });

            // User cancelled
            section.find("input[name='cancel']").click(function() {
                modal.modalWindow('close');
            });
        }
    });
}

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 jQuery DataPicker
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
var selected = new Date().getTime();

function removeDatePicker() {
    jQuery('#ui-datepicker-div').remove();
}

/**
 * Initialize jQuery-UI calendar
 * @param data_availability: associative array providing journal club sessions and their information
 */
var inititdatepicker = function (data_availability) {

    $('.datepicker').each(function() {
        var force_select = $(this).data('view') !== undefined ? $(this).data('view') === 'edit' : false;
        $(this).datepicker({
            defaultDate: selected,
            firstDay: 1,
            dateFormat: 'yy-mm-dd',
            inline: true,
            showOtherMonths: true,
            beforeShowDay: function(date) {
                return renderCalendarCallback(date, data_availability, force_select);
            }
        });
    });
};

/**
 * Initialize jQuery-UI calendar
 * @param data_availability: associative array providing journal club sessions and their information
 */
var init_submission_calendar = function (data_availability) {
    $('.datepicker_submission').each(function() {
        var force_select = $(this).data('view') !== undefined ? $(this).data('view') === 'edit' : false;
        $(this).datepicker({
            defaultDate: selected,
            firstDay: 1,
            dateFormat: 'yy-mm-dd',
            inline: true,
            showOtherMonths: true,
            beforeShowDay: function(date) {
                return renderCalendarCallback(date, data_availability, force_select);
            },
            onSelect: function(dateText, inst) {
                refresh_date(inst, planned_sessions[dateText]);
            }
        });
    });
};

/**
 * Initialize jQuery-UI calendar
 * @param data_availability: associative array providing journal club sessions and their information
 */
var initAvailabilityCalendar = function (data_availability) {
    var formid = $('#availability_calendar');
    formid.datepicker({
        defaultDate: selected,
        onSelect: function(dateText, inst) {
            selected = $(this).datepicker('getDate').getTime();

            jQuery.ajax({
                url: "php/form.php",
                type: "post",
                async: true,
                data: {
                    update_user_availability: true,
                    date: dateText
                },
                success: function() {
                    loadCalendarAvailability();
                }
            });
        },
        firstDay: 1,
        dateFormat: 'yy-mm-dd',
        inline: true,
        showOtherMonths: true,
        beforeShowDay: function(date) {
            return renderCalendarCallback(date, data_availability);
        }
    });

};

var planned_sessions = {};

/**
 * Render JQuery Datepicker
 * @param date
 * @param data
 * @param force_select
 * @returns {*}
 */
function renderCalendarCallback(date, data, force_select) {
    var day = date.getDay();
    var cur_date = $.datepicker.formatDate('dd-mm-yy', date);
    var booked = $.inArray(cur_date, data.booked);
    var css = null;
    var text = null;
    var clickable = force_select;

    // If there are sessions planned on this day
    if (booked > -1) {
        css = [];
        planned_sessions[$.datepicker.formatDate('yy-mm-dd', date)] = data.session_id[booked];
        var rem = data.slots[booked] - data.nb[booked]; // Number of presentations available that day
        var type = data.renderTypes[booked];
        text = type + ": (" + rem + " slot(s) available)";
        clickable = rem > 0 || force_select;
        if (data.nb[booked] === 0) {
            css.push("jc_day");
        } else if (data.nb[booked] < data.slots[booked]) {
            css.push("jc_day jc_day_rem");
        } else {
            css.push("jc_day full_day");
            text = type + ": Booked out";
        }

        var isAvailable = $.inArray(cur_date, data.Availability);
        if (isAvailable > -1) {
            css.push("not_available");
            text = "You are not available this day";
        }

        var isAssigned = $.inArray(cur_date, data.Assignments);
        if (isAssigned > -1) {
            css.push("assigned");
            text = "You are presenting this day";
        }
        css = css.join(' ');
        return [clickable, css, text];
    } else {
        return [clickable, "", "No session planned on this day"];
    }
}

/**
 * Refresh submission form when date is changed
 * @param el: input selector
 * @param session_id: session id corresponding to new date
 */
function refresh_date(el, session_id) {
    var form = $(el.input[0].form);
    var input = form.find('input[name="session_id"]');
    if (input !== undefined && input.length > 0) {
        input.val(session_id)
    } else {
        form.append("<input type='hidden' name='session_id' value='" + session_id + "' />")
    }
}

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Email
 *%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

/**
 * Add Recipients to email list
 *
 * @param el
 */
function addRecipients(el) {
    var input = $('.select_emails_selector');
    var container = $('.select_emails_container');

    var form = input.length > 0 ? $(input[0].form) : $();
    var id = input.val();
    var div = container.find('.select_emails_list');
    container.find('.select_emails_list').find('.mailing_recipients_empty').remove();

    var email_input = form.find("input[name='emails']");

    jQuery.ajax({
        url: 'php/router.php?controller=MailManager&action=addRecipients',
        type: 'post',
        data: {
            add_emails: id
        },
        async: true,
        success: function (data) {
            var json = jQuery.parseJSON(data);
            if (json.status) {
                if (email_input !== undefined && email_input.length > 0) {
                    var emails = email_input.val().split(',');
                    emails.push(json.ids);
                    emails = (emails[0] === "") ? emails.slice(1,emails.length):emails;
                    email_input.val(emails.join(','));
                } else {
                    form.append("<input name='emails' type='hidden' value='"+json.ids+"'/>");
                }
                div.append(json.content);
            }
        }
    });
}

/**
 * Send email to recipients
 *
 * @param el: input selector
 * @return {boolean}
 */
function sendToRecipients(el) {
    var form = el.length > 0 ? $(el[0].form) : $();

    // Check if recipients have been added
    var div = $('.select_emails_container').find('.select_emails_list');
    div.find('.mailing_recipients_empty').remove();
    if (!$.trim( div.html() ).length) {
        div.html('<p class="mailing_recipients_empty sys_msg warning leanmodal" id="warning">You must select ' +
            'recipients before sending your email!</p>');
        return true;
    }

    // Check if form has been filled in properly
    if (!checkform(form)) {return false;}

    var callback = function() {
        // Get data
        var data = getData(form);
        var attachments = [];

        form.find('input.upl_link').each(function() {
            attachments.push($(this).val());
        });
        attachments = attachments.join(',');
        data = modArray(data, 'attachments', attachments);

        // Process data
        processAjax($('.mailing_container'), data, false, "php/router.php?controller=MailManager&action=sendToRecipients");
    };

    // Shall we publish this email content as news (in case the email is sent to everyone).
    if ($('#make_news').val() === 'yes') {
        trigger_modal($(this));
        var msg = 'The option "Add as news" is set to "Yes", which means the content of your email will be ' +
            'published as a news.' + ' Do you want to continue?';
        confirmationBox($(this), msg, 'Continue', callback);
    } else {
        callback();
    }
    return true;
}

/**
 * Test email settings
 *
 * @param el
 */
function testEmailSettings(el) {
    var form = el.length > 0 ? $(el[0].form) : $();
    var data = form.serializeArray();
    processAjax(form, data, false, 'php/router.php?controller=MailManager&action=send_test_email');
}

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Logout
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
/**
 * Log out the user and trigger a modal window informing the user he/she has been logged out
 */
var logoutTemplate = "<div class='logoutWarning'><div class='logout_msg'></div><div class='logout_button'>OK</div></div>";

var login_start = null, login_expire = null, login_warning = null;
var logoutContainer = $('.logoutWarning');

/**
 * Logout
 */
function logout() {
    if (logoutContainer.length === 0) {
        $('body').append(logoutTemplate);
        logoutContainer = $('.logoutWarning');
    }

    login_start = null;
    login_expire = null;
    jQuery.ajax({
        url: 'php/router.php?controller=SessionInstance&action=destroy',
        type: 'POST',
        async: true,
        success: function (data) {
            var json = jQuery.parseJSON(data);

            logoutContainer.find('.logout_msg').html("You have been logged out");
            logoutContainer.find('.logout_button').html("OK");
            logoutContainer.fadeIn(200);

            setTimeout(function () {
                logoutContainer
                    .fadeOut(200)
                    .empty()
                    .hide();
                window.location = json;
            }, 3000);
        }
    });
};

/**
 * Check login status and expiration
 */
function check_login() {
    jQuery.ajax({
        url: 'php/router.php?controller=SessionInstance&action=checkLogin',
        type: "post",
        async: true,
        success: function(data) {
            var json = jQuery.parseJSON(data);
            if (json !== false) {
                login_start = json.start;
                login_expired = json.expired;
                login_warning = json.warning;
                login_remaining = json.remaining;

                if (login_remaining <= login_warning && login_remaining > 0) {
                    displaySessionTimeOut(login_remaining);
                } else if (login_remaining < 0) {
                    logout();
                }
            }
        }
    });
}

/**
 * Display warning message with time until logout
 * 
 * @param login_remaining Time until logout 
 */
function displaySessionTimeOut(login_remaining) {
    if (logoutContainer.length === 0) {
        $('body').append(logoutTemplate);
        logoutContainer = $('.logoutWarning');
    }

    var ms = 1000*Math.round(login_remaining); // round to nearest second
    var d = new Date(ms);
    var minutes = (d.getUTCMinutes() < 10) ? '0'+d.getUTCMinutes(): d.getUTCMinutes();
    var secondes = (d.getUTCSeconds() < 10) ? '0'+d.getUTCSeconds(): d.getUTCSeconds();
    logoutContainer.find('.logout_msg').html('You will be automatically logged out in ' + minutes + ':' + secondes + ' due to inactivity');
    logoutContainer.find('.logout_button').addClass('extend_session').html('Extend');
    if (!logoutContainer.is(':visible')) {
        logoutContainer.fadeIn(200);
    }
}

/**
 * Extend session
 */
function extend_session() {
    // Check login status
    jQuery.ajax({
        url: 'php/router.php?controller=SessionInstance&action=extendSession',
        type: "post",
        async: true,
        success: function(data) {
            var json = jQuery.parseJSON(data);
            if (json !== false) {
                login_start = json.start;
                login_expire = json.expire;
                login_warning = json.warning;
                logoutContainer.hide();
            }
        }
    });
}

/**
 * Show login dialog box
 */
function popLogin() {
    $('#login_button').click();
}

/**
 *
 * @returns {boolean}
 */
function process_email() {
    var form = $(this).length > 0 ? $($(this)[0].form) : $();
    var el = $('.mailing_container');

    // Check if recipients have been added
    var div = $('.select_emails_container').find('.select_emails_list');
    div.find('.mailing_recipients_empty').remove();
    if (!$.trim( div.html() ).length) {
        div.html('<p class="mailing_recipients_empty sys_msg warning leanmodal" id="warning">You must select ' +
            'recipients before sending your email!</p>');
        return true;
    }

    // Check if form has been filled in properly
    if (!checkform(form)) {return false;}

    var callback = function() {
        // Get data
        var data = form.serializeArray();
        var content = tinyMCE.get('spec_msg').getContent();
        var attachments = [];
        form.find('input.upl_link').each(function() {
            attachments.push($(this).val());
        });
        attachments = attachments.join(',');
        data = modArray(data, 'body', content);
        data = modArray(data, 'attachments', attachments);

        // Process data
        processAjax(el, data, null, "php/form.php");
    };

    // Shall we publish this email content as news (in case the email is sent to everyone).
    var id = $('.select_emails_selector').val();
    if ($('#make_news').val() === 'yes') {
        trigger_modal($(this));
        var msg = 'The option "Add as news" is set to "Yes", which means the content of your email will be ' +
            'published as a news.' + ' Do you want to continue?';
        confirmationBox($(this), msg, 'Continue', callback);
    } else {
        callback();
        close_modal();
    }
    return true;
}
/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Modal windows
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
var modalpubform = $('.modal_section#submission_form');

/**
 * Display presentation's information in a modal window
 * @param data
 */
function show_submission_details (data) {
    var el = data['destination'] !== undefined ? $(data['destination']) : modalpubform;
    data['show_submission_details'] = true;
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: false,
        data: data,
        success: function (data) {
            var result = jQuery.parseJSON(data);
            load_section(result);
        }
    });
}

/**
 * Get width of hidden objects (useful to get width of submenus)
 * @param obj (DOM element)
 * @returns {*}
 */
function realWidth(obj) {
    var clone = obj.clone();
    clone.css("visibility", "hidden");
    $('body').append(clone);
    var width = clone.outerWidth();
    clone.remove();
    return width;
}

/**
 * Hide active sub-menus
 */
function clear_active_submenu() {
    // Hide sub menus
    if ($(".submenu").is(':visible')) {
        $('.submenu').hide();
    }

    $('.submenu_trigger').each(function() {
        $(this).parent('li').removeClass("active_submenu");
    });
}

/**
 * Show sub menu
 * @param el
 * @returns {boolean}
 */
function show_submenu(el) {
    var menuEl = el.parent('li');
    if (menuEl.hasClass('active_submenu')) {
        return true;
    }

    clear_active_submenu();

    menuEl.addClass('active_submenu');
    var position = menuEl.position();
    var height = menuEl.outerHeight();
    var id = el.attr('id');
    var submenu;

    if (el.closest('div').hasClass('sideMenu')) {
        submenu = $(".sideMenu nav.submenu#" + id);
        submenu.toggle(200);
    } else {
        submenu = $(".topnav nav.submenu#" + id);
        submenu
            .css({
                'left': position.left + "px",
                'top': position.top + height + "px"
            })
            .toggle(200);
    }
}

/**
 * Process votes
 */
function process_vote(el) {
    // If user is not logged in, then prompt login window
    if (login_start === null) {popLogin(); return false;}

    var parent = el.parent('.vote_container');
    var data = parent.data();
    data['process_vote'] = true;
    jQuery.ajax({
        url: 'php/form.php',
        data: data,
        type: 'post',
        async: true,
        success: function(json) {

            var result = jQuery.parseJSON(json);
            if (result.status !== false) {
                if (!result.state) {
                    el.toggleClass('vote_liked vote_default');
                    parent.data('operation', 'add');
                    parent.attr('data-operation', 'add');
                } else {
                    el.toggleClass('vote_default vote_liked');
                    parent.data('operation', 'delete');
                    parent.attr('data-operation', 'delete');
                }
                el.next().html(result.count);
            }
        }
    });
}

/**
 * Process bookmark
 */
function process_bookmark(el) {
    var data = el.data();
    data['process_vote'] = true;
    jQuery.ajax({
        url: 'php/form.php',
        data: data,
        type: 'post',
        async: true,
        success: function(json) {
            var result = jQuery.parseJSON(json);
            if (result.status === true) {
                if (!result.state) {
                    el.toggleClass('bookmark_on bookmark_off');
                    el.attr('data-operation', 'add');
                    el.data('operation', 'add');

                } else {
                    el.toggleClass('bookmark_off bookmark_on');
                    el.attr('data-operation', 'delete');
                    el.data('operation', 'delete');
                }
            }
        }
    });
}

/**
 * Process submission form
 * @param el: submit input selector
 * @param e: events
 * @returns {boolean}
 */
function process_post(el, e) {
    e.preventDefault();
    var form = el.length > 0 ? $(el[0].form) : $();

    // Check if the form has been fully completedf
    if (!checkform(form)) { return false;}

    // Check if files have been uploaded and attach them to this presentation
    var uploadInput = form.find('.upl_link');
    if (uploadInput[0]) {
        var links = [];
        uploadInput.each(function () {
            var link = $(this).val();
            links.push(link);
        });
        links = links.join(',');
        form.append("<input type='hidden' name='link' value='"+links+"'>");
    }

    // Form data
    var data = getData(form);

    var controller = form.find('input[name="controller"]').val();

    // Callback function
    var callback = function (result) {
        if (result.status === true) {
            var container_id = controller.toLowerCase() + '_form';
            $('section#' + container_id + ', .modal_section#' + container_id).empty();
            var id = form.find('input[name="id"]').val().length > 0
            && form.find('input[name="id"]').length > 0 ? form.find('input[name="id"]').val() : undefined;
            var operation = id !== undefined ? 'edit' : 'new';

            get_submission_form({
                'controller': controller,
                'action': 'get_form',
                'operation': operation,
                'id': id,
                'destination': '#' + controller.toLowerCase() + '_container'}
            );

        } else {
            return false;
        }
    };

    // Find tinyMCE textarea and gets their content
    var tinyMCE_el = form.find('.wygiwgm');
    if (is_editor_active(tinyMCE_el) && tinyMCE.get(tinyMCE_el.attr('id')).getContent().length > 0) {
        tinyMCE_el.each(function() {
            var content = tinyMCE.get($(this).attr('id')).getContent();
            data = modArray(data, $(this).attr('name'), content);
        })
    }

    // AJAX call
    processAjax(el.closest('.form_container'), data, callback, "php/form.php");
    e.stopImmediatePropagation();
}

/**
 * Process submission form
 * @param el: submit input selector
 * @param e: events
 * @returns {boolean}
 */
function process_submission(el, e) {
    e.preventDefault();
    
    var form = el.length > 0 ? $(el[0].form) : $();

    // Check if the form has been fully completed
    if (!checkform(form)) { return false;}

    // Submission type
    var type = form.find("select[name='type']").val();

    // Check if a data has been selected (except for suggestion)
    var date_input = form.find("input[type=date]");
    if (date_input !== undefined && date_input.length > 0) {
        var date = date_input.val();
        if (date === undefined || date.length === 0) {
            showfeedback('<p id="warning">You must choose a date!</p>');
            date_input.focus();
            return false;
        }
    }

    // Check if files have been uploaded and attach them to this presentation
    var uploadInput = form.find('.upl_link');
    if (uploadInput[0]) {
        var links = [];
        uploadInput.each(function () {
            var link = $(this).val();
            links.push(link);
        });
        links = links.join(',');
        form.append("<input type='hidden' name='media' value='"+links+"'>");
    }

    // Form data
    var data = getData(form);

    // Get controller name
    var controller = form.find('input[name="controller"]').val();

    // Callback function
    var callback = function (result) {
        if (result.status === true) {
            var container_id = controller.toLowerCase() + '_form';
            $('section#' + container_id + ', .modal_section#' + container_id).empty();
            var id = form.find('input[name="id"]').val().length > 0
            && form.find('input[name="id"]').length > 0 ? form.find('input[name="id"]').val() : undefined;
            var operation = id !== undefined ? 'edit' : 'new';
            if (in_modal(el)) {
                el.find('.modalContainer').modalWindow('close');
                location.reload();
            } else {
                get_submission_form({
                    'controller': controller,
                    'action': 'get_form',
                    'operation': operation,
                    'id': id,
                    'destination': '#' + controller.toLowerCase() + '_container'}
                );
            }
        } else {
            return false;
        }
    };

    // AJAX call
    processAjax(el.closest('.form_container'), data, callback, "php/form.php");

    e.stopImmediatePropagation();
}


$(document).ready(function () {
    var previous;

    setInterval(check_login, 5000);

    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Main body
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    $(document)

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Header menu/Sub-menu
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Dropdown menu
        .on('click','#float_menu',function () {
            var position = $(this).position();
            var height = $(this).outerHeight();
            var windowHeight = $(window).height();
            $('.sideMenu')
                .css({
                    'top': height + "px",
                    'left': position.left + "px",
                    'height': (windowHeight - height) + "px",
                    'max-height': (windowHeight - height) + "px"
                })
                .animate({width:'toggle'});
        })

        .on('mouseover', '.submenu_trigger', function(e) {
            e.preventDefault();
            show_submenu($(this));
        })

        // Main menu sections
        .on('click',".menu-section",function (e) {
            e.preventDefault();
            e.stopPropagation();
            var link = $(this).find('a');
            $(".menu-section").each(function() {
                $(this).removeClass("activepage");
            });

            $(this).addClass("activepage");

            if (link.is('[id]')) {
                // Parse url
                var query = link.attr("href");
                var vars = query.split("&");
                var pair = vars[0].split("=");
                var page_to_load = pair[1];

                var param = (link.is('[data-param]'))? link.attr('data-param') : false;
                getPage(page_to_load, param);
                $('.submenu, .dropdown').hide();
            }

            var sideMenu = $('.sideMenu');
            if (sideMenu.is(':visible')) {
                sideMenu.animate({width:"toggle"});
            }
        })

        // Hide dropdown menus when not clicked
        .click(function (e) {
            var nav = $("nav");
            var sideMenu = $('.sideMenu');
            var float_menu = $('#float_menu');
            if (!float_menu.is(e.target) && float_menu.has(e.target).length === 0) {
                if (!nav.is(e.target) && nav.has(e.target).length === 0) {
                    $('.submenu').hide();
                    clear_active_submenu();
                }

                if (sideMenu.is(':visible') && !sideMenu.is(e.target) && sideMenu.has(e.target).length === 0) {
                    sideMenu.animate({width:"toggle"});
                }

            }
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         JQuery_UI Calendar
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        .on('mouseenter','#core, .submission',function (e) {
            e.preventDefault();
        })

        .on("change", "#availability_calendar", function () {
            alert($(this).val());
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         User Profile
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
		// Send a verification email to the user if a change of password is requested
        .on('click',".change_pwd",function (e) {
            e.preventDefault();
            var form = $(this).closest('form');

            var email = $(this).attr("id");
            var data = {request_password_change: true, email: email};

            var callback = function(json) {
                var result = jQuery.parseJSON(json);
                trigger_modal($(this), false);
                dialogBox($(this), result, 'Modify event');
            };
            processAjax(form, data, callback , "php/form.php");
        })

		// Password change form (email + new password)
        .on('click',".conf_changepw",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function (result) {
                if (result.status === true) {
                    setTimeout(logout,2000);
                }
            };
            
            processForm(form, callback, form.attr('action'));
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin tools
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // User management tool: sort users
		.on('click','.user_select',function (e) {
            e.preventDefault();
            var filter = $(this).data('filter');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    user_select: filter
                    },
                success: function (data) {
                    var result = jQuery.parseJSON(data);
					$('#user_list').html(result);
                }
            });
            return false;
        })

        // User Management tool: Modify user status
        .on('change','.modify_status',function (e) {
            e.preventDefault();
            var div = $('#user_list');
            var username = $(this).attr("data-user");
            var option = $(this).val();
            var data = {modify_status: true,username: username,option: option};
            var callback = function (result) {
                setTimeout(function () {
                    $('#user_list').html(result.content);
                },2000);
            };
            processAjax(div, data, callback, "php/form.php");
        })

        // User Management tool: Modify user status
        .on('change','.account_action',function (e) {
            e.preventDefault();
            var div = $('#user_list');
            var username = $(this).attr("data-user");
            var action = $(this).val();
            var data = {username: username};
            var callback = function (result) {
                setTimeout(function () {
                    $('#user_list').html(result.content);
                },2000);
            };
            processAjax(div, data, callback, "php/router.php?controller=Users&action=" + action);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Mailing
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Test email host settings
        .on('click', '.test_email_settings', function(e) {
            e.preventDefault();
            testEmailSettings($(this));
        })

        // Add recipients
        .on('change', '.select_emails_selector', function(e) {
            e.preventDefault();
            addRecipients($(this));
        })

        // Delete recipients
        .on('click', '.added_email_delete', function (e) {
            var form = $(this).closest('form');
            var id = $(this).attr('id');
            var div = $('.added_email#'+id);
            // Remove id from input list
            var email_input = form.find("input[name='emails']");
            var emails = email_input.val().split(',');
            var index = emails.indexOf(id);
            if (index > -1) {
                emails.splice(index, 1);
            }
            email_input.val(emails.join(','));
            div.remove();
        })

		// Send an email to the mailing list
        .on('click','.mailing_send',function (e) {
            e.preventDefault();
            sendToRecipients($(this));
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Sessions
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        /**
         * Show  logs
         */
        .on('click', '.show_log', function(e) {
            e.preventDefault();
            var el = $(this);
            loadContent(el, function(json) {
                $('.log_list_item_container').each(function() {
                    $(this).removeClass('log_list_active');
                });
                var name = el.closest('.log_container').attr('id');
                //var div = $(el.data('destination'));
                var item = el.closest('.log_list_item_container');
                //div.html(json);
                item.addClass('log_list_active');
            });
        })

        /**
         * Search in logs
         */
        .on('click', '.search_log', function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            input.data({
                'url': form.attr('action') + form.find("input[name='search']").val(),
                'destination': ".log_content_container#" + $(this).attr('id')
            });
            loadContent(input);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Sessions
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Add a session/presentation type
        .on('click','.type_add',function (e) {
            e.preventDefault();
            var classname = $(this).attr('data-class');
            var typename = $('input#new_' + classname + '_type').val();
            if ($(this).hasClass('wrongField')) {
                $(this).removeClass('wrongField');
            }
            if (typename.length === 0) {
                $(this).siblings('input').addClass('wrongField');
                return true;
            }
            var el = $(this);
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    add_type: classname,
                    typename: typename
                },
                beforeSend: function() {
                    el.toggleClass('addBtn loadBtn');
                },
                complete: function() {
                    el.toggleClass('addBtn loadBtn');
                },
                success: function (data) {
                    var result = jQuery.parseJSON(data);
                    if (result !== false) {
                        $('.type_list#'+classname).html(result);
                        $('input#new_'+classname+'_type').empty();
                    } else {
                        showfeedback("<span class='sys_msg warning'>We could not add this new category", "feedback_" + classname);
                    }
                }
            });
        })

        // Delete a session/presentation type
        .on('click','.type_del',function (e) {
            e.preventDefault();
            var typename = $(this).attr('data-type');
            var classname = $(this).attr('data-class');
            var el = $(this);
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    del_type: classname,
                    typename: typename},
                beforeSend: function() {
                    el.addClass('loadBtn');
                },
                complete: function() {
                    el.removeClass('loadBtn');
                },
                success: function (data) {
                    var result = jQuery.parseJSON(data);
                    if (result.status !== false) {
                        $('.type_list#' + classname).html(result);
                    } else {
                        validsubmitform( $('.type_list#' + classname), data);
                    }
                }
            });
        })

        // Change default session type
        .on('change','.type_default',function (e) {
            e.preventDefault();
            var class_name = $(this).attr('id');
            var div = $('#' + class_name + '_types_options');
            var type = $(this).val();
            var data = {type_default: type, class_name: class_name};
            processAjax(div, data, false, "php/form.php");
        })

        .on('change', '.repeated_session', function(e) {
            var val = $(this).val();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();

            var hidden_fields = form.find('.settings_hidden');
            if (val == 1) {
                hidden_fields.each(function() {
                    $(this).fadeIn();
                });
            } else {
                hidden_fields.each(function() {
                    $(this).fadeOut();
                });
            }
        })

        // Select session to show
        .on('change','.selectSession',function (e) {
            e.preventDefault();
            var nbsession = $(this).val();
            var status = ($(this).attr('data-status').length) ? $(this).data('status'):'admin';
            var view = ($(this).data('view') === undefined) ? 'simple' : $(this).data('view');
            var data = {show_session: nbsession, status: status, view: view};
            var div = $('#sessionlist');
            var callback = function (result) {
                $('#sessionlist')
                    .html(result)
                    .fadeIn(200);
            };
            processAjax(div, data, callback, "php/form.php");
        })

        // Modify speaker
        .on('focus','.modSpeaker', function () {
            // Store the current value on focus and on change
            previous = $(this).val();
        })

        .on('change', '.modSpeaker', function () {
            // Do something with the previous value after the change
            var speaker = $(this).val();
            var container = $(this).closest('.pres_container');
            var pres_id = container.attr('id');
            var session_id = $(this).closest('.session_div').data('id');
            var data = {
                modSpeaker: speaker,
                previous: previous,
                presid: pres_id,
                session_id: session_id
            };
            processAjax($(this).closest('.session_div'), data, null, "php/form.php");
        })

        .on('submit', 'form', function(e) {
            if (!checkform($(this))) {
                e.stopPropagation();
            }
        })

        // Modify session
        .on('click','.modify_session',function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var session_id = form.find('input[name="id"]').val();
            var url = form.attr('action');

            var process = function(operation) {
                // Add/Update operation input
                if (form.find('input[name="operation"]').length === 0) {
                    form.append("<input type='hidden' name='operation' value='" + operation + "' />");
                } else {
                    form.find('input[name="operation"]').attr('value', operation);
                }

                // Get form data
                var data = getData(form);

                // Process data
                processAjax(form, data, function() {
                    input.modalTrigger('close');
                }, url);
            };

            jQuery.ajax({
                url: 'php/router.php?controller=Session&action=is_recurrent',
                type: 'post',
                data: {'id': session_id},
                async: true,
                success: function(data) {
                    if (jQuery.parseJSON(data) === true) {
                        trigger_modal(input, false);
                        var msg = "<p>This event is recurrent</p>. " +
                            "<input type='submit' value='Modify this occurrence only' class='callback_trigger' data-operation='present'/>" +
                            "<input type='submit' value='Modify all future occurrences' class='callback_trigger' data-operation='future'/>" +
                            "<input type='submit' value='Modify all occurrences' class='callback_trigger' data-operation='all'/>";
                        dialogBox(input, msg, 'Modify event', function(el) {
                            process(el.data('operation'));
                        });
                    } else {
                        process('present');
                    }
                }
            });
        })

        // Delete session
        .on('click','.delete_session',function (e) {
            e.preventDefault();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            var session_id = form.find('input[name="id"]').val();
            var input = $(this);

            var process = function(operation) {
                // Add/Update operation input
                if (form.find('input[name="operation"]').length === 0) {
                    form.append("<input type='hidden' name='operation' value='" + operation + "' />");
                } else {
                    form.find('input[name="operation"]').attr('value', operation);
                }

                // Get form data
                var data = form.serializeArray();
                data.push({name: 'delSession', value: true});

                // Process data
                processAjax(form, data, close_modal, 'php/form.php');
            };

            jQuery.ajax({
                url: 'php/form.php',
                type: 'post',
                data: {'is_recurrent': session_id},
                async: true,
                success: function(data) {

                    if (jQuery.parseJSON(data) === true) {
                        trigger_modal(input, false);
                        var msg = "<p>This event is recurrent</p>. " +
                            "<input type='submit' value='Delete this occurrence only' class='callback_trigger' data-operation='present'/>" +
                            "<input type='submit' value='Delete all future occurrences' class='callback_trigger' data-operation='future'/>" +
                            "<input type='submit' value='Delete all occurrences' class='callback_trigger' data-operation='all'/>";
                        dialogBox(input, msg, 'Delete event', function(el) {
                            process(el.data('operation'));
                        });
                    } else {
                        confirmationBox(input, 'Are you sure you want to delete this session?', 'Delete', function () {
                            process('present');
                        });
                    }
                }
            });
        })

        // Modify session type
        .on('change','.mod_session_type',function (e) {
            e.preventDefault();
            var prop = $(this).attr('name');
            var value = $(this).val();
            var sessionDiv = $(this).closest('.session_div');
            var sessionID = sessionDiv.data('id');
            var data = {mod_session_type: true, session: sessionID, prop: prop, value: value};
            processAjax(sessionDiv, data, null, "php/form.php");
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Publication lists (Archives/user publications)
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Sort publications by years
		.on('change','.archive_select',function (e) {
            e.preventDefault();
            var year = $(this).val();
            var data = {select_year: year};
            var callback = function (result) {
                $('#archives_list').html(result);
            };
            var div = $('#archives_list');
            processAjax(div, data, callback, "php/form.php");
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Presentation submission
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Select a wish
        .on('change','#select_wish',function (e) {
            e.preventDefault();
            $(this).data('id', $(this).val());
            loadContent($(this));
         })

        .on('click', '.loadContent', function(e) {
            e.preventDefault();
            loadContent($(this));
        })

        .on('change', '.actionOnSelect', function(e) {
            e.preventDefault();
            actionOnSelect($(this));
        })

        .on('click', '.select_suggestion', function(e) {
            e.preventDefault();
            get_submission_form($(this).data());
        })

        // Show download list
        .on('click','.dl_btn',function () {
            $(".dlmenu").toggle();
        })

        // Show uploaded file
        .on('click','.link_name',function () {
            var uplname = $(this).attr('id');
            var url = "uploads/"+uplname;
            window.open(url, '_blank');
        })

        // Select submission type
         .on('change', '.change_pres_type', function (e) {
            e.preventDefault();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            var type = $(this).val();
            var id = $(this).attr('id').split('_');
            var controller = id[0];
            var pres_id = id[1];
            var callback = function(result) {
                $('.special_inputs_container').html(result);
            };
            var data = {getFormContent: type, controller: controller, id: pres_id};
            processAjax($('.special_inputs_container'), data, callback, "php/form.php");
         })
             
        // Submit a presentation
        .on('click','.submit_pres',function (e) { process_submission($(this), e) })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Votes
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        .on('click', '.vote_icon', function() {process_vote($(this))})

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Bookmarks
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        .on('click', '.bookmark_container', function () {process_bookmark($(this))})

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Login/Logout triggers
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Log out
        .on('click',"#logout",function (e) {
            e.preventDefault();
            logout();
        })

        .on('click', '.logout_button', function() {
            $('.logoutWarning').fadeOut(200).toggle();
        })

        .on('click', '.extend_session', function() { extend_session(); })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Submission triggers
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

        .on('click', '.get_suggestion_list', function(e) {
            var data = {'get_suggestion_list': true};
            // First we remove any existing submission form
            var callback = function (result) {
                modalpubform
                    .html(result)
                    .fadeIn(200);
            };
            processAjax(modalpubform, data, callback, "php/form.php");
        })

        .on('click', '.load_content', function(e) {
            e.preventDefault();
            var query = $(this).attr("href");
            var vars = query.split("?");
            var pairs = vars[1].split("&");
            var args = {};
            for (var i=0; i<pairs.length; i++) {
                var pair = pairs[i].split("=");
                args[pair[0]] = pair[1];
            }

            var operation = (args['op'] !== undefined) ? args['op'] : false;
            if (operation === 'wishpick') {
                var data = {'get_suggestion_list': true};
                // First we remove any existing submission form
                var callback = function (result) {
                    el
                        .html(result)
                        .fadeIn(200);
                };
                processAjax(el, data, callback, "php/form.php");
            } else {
                get_submission_form($(this).data());
            }
        })

    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Modal Window
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Bind leanModal to triggers
        .on('mouseover', ".leanModal", function(e) {
            e.preventDefault();
            var callback = function() {
                loadWYSIWYGEditor();

                // Load JCM calendar
                loadCalendarSubmission();
            };
            trigger_modal($(this), true, callback);
        })

        .on('click', '.go_to_section', function(e) {
            e.preventDefault();
            $(this).closest('.modalContainer').modalWindow('go_to_section', ($(this).data()));
        })

        // Dialog change password
        .on('click',".modal_trigger_changepw",function (e) {
            e.preventDefault();
            $(this).modalTrigger('show_section', 'user_changepw');
        })

        // Going back to Login Forms
        .on('click',".back_btn",function (e) {
            e.preventDefault();
            $(this).closest('.modalContainer').modalWindow('go_to_previous', ($(this).data('prev')));
            return false;
        })

        .on('click', '.show_section', function(e) {
            e.preventDefault();
            $(this).modalTrigger('load_section', $(this).data());
        })

        .on('click', '.delete', function(e) {
            e.preventDefault();
            var el = $(this);
            var action = (el.data('action') !== undefined) ? el.data('action') : 'delete';
            var url = (el.data('controller') !== undefined) ?
                'php/router.php?controller=' + el.data('controller') + '&action=' + action : el.attr('href');
            confirmationBox(el, 'Are you sure you want to delete this item?', 'Delete', function () {
                jQuery.ajax({
                    url: url,
                    type: 'post',
                    data: {
                        'id': el.data('id')
                    },
                    async: true,
                    success: function(ajax) {
                        var result = jQuery.parseJSON(ajax);
                        if (result === true || (result.hasOwnProperty('status') && result.status === true)) {
                            showfeedback("<div class='sys_msg success'>Item deleted</div>", '.confirmation_text');
                            setTimeout(function() {
                                el.modalTrigger('close');
                                location.reload();
                            }, 2000);
                        } else {
                            showfeedback("<div class='sys_msg warning'>Item could not be deleted</div>", '.confirmation_text', false);
                        }
                    }
                });
            });
        })

        .on('click', '.user_delete', function(e) {
            e.preventDefault();
            var el = $(this);
            confirmationBox(el, 'Are you sure you want to delete your account?', 'Delete my account', function () {
                jQuery.ajax({
                    url: 'php/router.php?controller=Users&action=delete_user&username=' + el.data('username'),
                    type: 'post',
                    async: true,
                    success: function(ajax) {
                        var result = jQuery.parseJSON(ajax);
                        if (result === true || (result.hasOwnProperty('status') && result.status === true)) {
                            showfeedback("<div class='sys_msg success'>Account successfully deleted. You are going to be logged out.</div>", '.confirmation_text');
                            setTimeout(function() {
                                el.modalTrigger('close');
                                location.reload();
                            }, 2000);
                        } else {
                            showfeedback("<div class='sys_msg warning'>Sorry, we could not delete your account. Please, try later.</div>", '.confirmation_text', false);
                        }
                    }
                });
            });
        })

        // Login form
        .on('click'," .login",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function (result) {
                if (result.status === true) {
                    location.reload();
                }
            };
            processForm(form, callback, 'reload');
            e.stopImmediatePropagation();
        })

        // Sign Up Form
        .on('click', ".register",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function (result) {
                if (result.status === true) {
                    input.modalTrigger('close');
                }
            };
            processForm(form, callback);
            e.stopImmediatePropagation();
        });
});
