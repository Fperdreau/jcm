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

/**
 * Trigger modal window
 * @param el
 */
function trigger_modal(el) {
    if (el.data('leanModal') === undefined) {
        el.leanModal();
        el.data('leanModal', true);
        el.click();
    }
}
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
    data['get_submission_form'] = true;
    var el = (data['destination'] === undefined) ? $('.submission_container') : $(data['destination']);

    // First we remove any existing submission form
    var callback = function (result) {
        el
            .html(result)
            .fadeIn(200)
            .find('textarea').html(result.content);
        tinyMCE.remove();
        window.tinymce.dom.Event.domLoaded = true;
        tinymcesetup();

    };
    processAjax(el, data, callback, "php/form.php");

    // Load JCM calendar
    loadCalendarSubmission();
};

/**
 * Display form to post a news
 * @param postid
 */
var showpostform = function (postid) {
    var el = $('.postcontent');
    var data = {post_show: true, postid: postid};
    var callback = function (result) {
        var txtarea = "<textarea name='content' id='post_content' class='tinymce'>" + result.content + "</textarea>";
        $('.postcontent')
            .empty()
            .html(result.form)
            .fadeIn(200);
        $('.post_txtarea')
            .html(txtarea)
            .show();
        tinyMCE.remove();
        window.tinymce.dom.Event.domLoaded = true;
        tinymcesetup();
    };
    processAjax(el, data, callback, "php/form.php");
};

/**
 * Render a confirmation box
 * @param el: clicked element
 * @param txt: message to display in dialog box
 * @param txt_btn: text of confirmation button
 * @param callback: callback function (called if user has confirmed)
 */
function confirmation_box(el, txt, txt_btn, callback) {
    trigger_modal(el);

    var container = $('.modalContainer');

    // Remove confirmation section if it already exist
    if (container.find('.modal_section#confirmation_box').length > 0) {
        container.find('.modal_section#confirmation_box').remove();
    }

    // Render section
    var html = "<div class='modal_section' id='confirmation_box' data-title='Confirmation'>" +
        "<div class='sys_msg warning'>" + txt + "</div>" +
        "<div class='action_btns'>" +
        "<div class='one_half'><input type='submit' name='cancel' class='pub_back_btn fa-angle-double-left' " +
        "value='Cancel'></div>" +
        "<div class='one_half last'><input type='submit' name='confirmation' value='" + txt_btn + "'></div>" +
        "</div>" +
        "</div>";
    container.find('.popupBody').append(html);

    // Show  section
    show_section('confirmation_box');
    var section = $('.modal_section#confirmation_box');
    
    // User has confirmed
    section.find("input[name='confirmation']").click(function() {
        close_modal();
        callback();
    });

    // User cancelled
    section.find("input[name='cancel']").click(function() {
        close_modal();
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
                var form = $(inst.input[0].form);
                var input = form.find('input[name="session_id"]');
                if (input !== undefined && input.length > 0) {
                    input.val(planned_sessions[dateText])
                } else {
                    form.append("<input type='hidden' name='session_id' value='" + planned_sessions[dateText] + "' />")
                }
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
        var type = data.session_type[booked];
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

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Logout
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
/**
 * Log out the user and trigger a modal window informing the user he/she has been logged out
 */
var logoutTemplate = "<div class='logoutWarning'><div class='logout_msg'></div><div class='logout_button'>OK</div></div>";
var logout = function () {
    if (logoutContainer.length == 0) {
        $('body').append(logoutTemplate);
    }

    login_start = null;
    login_expire = null;
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {logout: true},
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

var login_start = null, login_expire = null, login_warning = null;
var logoutContainer = $('.logoutWarning');
/**
 * Check login status and expiration
 */
function check_login() {
    if (logoutContainer.length == 0) {
        $('body').append(logoutTemplate);
        logoutContainer = $('.logoutWarning');
    }

    if (login_start == null) {
        // Check login status
        jQuery.ajax({
            url: 'php/form.php',
            data: {check_login: true},
            type: "post",
            async: true,
            success: function(data) {
                var json = jQuery.parseJSON(data);
                if (json !== false) {
                    login_start = json.start;
                    login_expire = json.expire;
                    login_warning = json.warning;
                }
            }
        });

    } else {
        var currentTime = Math.floor(new Date().getTime() / 1000);
        var remainingTime = login_expire - currentTime; // Seconds before expiration
        if (remainingTime <= login_warning && remainingTime > 0) {
            var ms = 1000*Math.round(remainingTime); // round to nearest second
            var d = new Date(ms);
            var minutes = (d.getUTCMinutes() < 10) ? '0'+d.getUTCMinutes(): d.getUTCMinutes();
            var secondes = (d.getUTCSeconds() < 10) ? '0'+d.getUTCSeconds(): d.getUTCSeconds();
            logoutContainer.find('.logout_msg').html('You will be automatically logged out in ' + minutes + ':' + secondes + ' due to inactivity');
            logoutContainer.find('.logout_button').addClass('extend_session').html('Extend');
            if (!logoutContainer.is(':visible')) {
                logoutContainer.fadeIn(200);
            }
        } else if (remainingTime < 0) {
            logout();
        }
    }
}

/**
 * Extend session
 */
function extend_session() {
    // Check login status
    jQuery.ajax({
        url: 'php/form.php',
        data: {extend_login: true},
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
    $('#login_button').leanModal().click();
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
            el
                .hide()
                .html(result)
                .fadeIn(200);
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
    var operation = data['operation'];
    jQuery.ajax({
        url: 'php/form.php',
        data: data,
        type: 'post',
        success: function(data) {
            var result = jQuery.parseJSON(data);
            if (result.status === true) {
                if (operation == 'delete') {
                    el.parent('.vote_container').html(result.msg);
                } else if (operation == 'add') {
                    el.parent('.vote_container').html(result.msg);
                }
            }
        }
    });
}

/**
 * Process bookmark
 */
function process_bookmark(el) {
    // If user is not logged in, then prompt login window
    if (login_start === null) {popLogin(); return false;}

    var data = el.data();
    data['process_vote'] = true;
    var operation = data['operation'];
    jQuery.ajax({
        url: 'php/form.php',
        data: data,
        type: 'post',
        success: function(data) {
            var result = jQuery.parseJSON(data);
            if (result === true) {
                if (operation == 'delete') {
                    el.toggleClass('bookmark_on bookmark_off');
                    el.attr('data-operation', 'add');
                } else if (operation == 'add') {
                    el.toggleClass('bookmark_off bookmark_on');
                    el.attr('data-operation', 'delete');
                }
            }
        }
    });
}


$(document).ready(function () {
    var previous;

    setInterval(check_login, 5000);

    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Main body
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    $('body')

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
            var data = {change_pw: true, email: email};
            processAjax(form, data, null , "php/form.php");
        })

		// Password change form (email + new password)
        .on('click',".conf_changepw",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            if (!checkform(form)) {return false;}
            var data = form.serialize();
            var callback = function (result) {
                if (result.status === true) {
                    setTimeout(logout,2000);
                }
            };
            processAjax(form, data, callback, "php/form.php");
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

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Mailing
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Test email host settings
        .on('click', '.test_email_settings', function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var data = form.serializeArray();
            data.push({name: 'test_email_settings', value: true});
            processAjax(form, data, false, 'php/form.php');
        })

        // Add emails
        .on('click', '.add_email', function (e) {
            e.preventDefault();
            var input = $('.select_emails_selector');
            var form = input.length > 0 ? $(input[0].form) : $();
            var name = input.find('option:selected').html();
            var id = input.val();
            var div = $('.select_emails_container').find('.select_emails_list');
            $('.select_emails_container').find('.select_emails_list').find('.mailing_recipients_empty').remove();

            var email_input = form.find("input[name='emails']");

            jQuery.ajax({
                url: 'php/form.php',
                type: 'post',
                data: {
                    add_emails: id
                },
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
        })

        .on('change', '.select_emails_selector', function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var name = input.find('option:selected').html();
            var id = input.val();
            var div = $('.select_emails_container').find('.select_emails_list');
            $('.select_emails_container').find('.select_emails_list').find('.mailing_recipients_empty').remove();
            var email_input = form.find("input[name='emails']");
            if (id === "all") {
                $("#make_news").find("option[value='yes']").prop('selected', true);
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'post',
                data: {
                    add_emails: id
                },
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
        })

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
                confirmation_box($(this), msg, 'Continue', callback);
            } else {
                callback();
                close_modal();
            }
            return true;
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Digest Maker
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        .on('click', '.mail_preview', function (e) {
            e.preventDefault();
            var operation = $(this).attr('id');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'post',
                async: true,
                data: {
                    preview: operation},
                success: function (data) {
                    var json = jQuery.parseJSON(data);
                    $('.mail_preview_container').hide().html(json.body).fadeIn(200);
                }
            });
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - News
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Select news to modify
        .on('change','.select_post',function (e) {
            e.preventDefault();
            var postid = $(this).val();
            showpostform(postid);
        })

        .on('click','.edit_post',function (e) {
            e.preventDefault();
            var postid = $(this).closest('.news-details').attr('id');
            showpostform(postid);
        })

        // Add a new post
        .on('click','.post_new',function (e) {
            e.preventDefault();
            showpostform(false);
        })

        // Delete a post
        .on('click','.post_del',function (e) {
            e.preventDefault();
            var postid = $(this).attr('data-id');
            var data = {post_del: true, postid: postid};
            var callback = function (result) {
                if (result.status === true) {
                    showpostform(false);
                }
            };
            processAjax($('.postcontent'), data, callback, "php/form.php");
        })

        // Add a news to the homepage
        .on('click','.submit_post',function (e) {
            e.preventDefault();
            var form = $(this).closest('#post_form');
            if (!checkform(form)) {return false;}
            var callback = function (result) {
                if (result.status === true) {
                    form.fadeOut();
                }
            };
            var data = form.serializeArray();
            var content = tinyMCE.get('post_content').getContent();
            data = modArray(data,'content',content);
            processAjax(form, data, callback, "php/form.php");
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Sessions
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        /**
         * Show  logs
         */
        .on('click', '.show_log', function(e) {
            e.preventDefault();
            var name = $(this).closest('.log_container').attr('id');
            var url = $(this).attr('href');
            var div = $('.log_content_container#' + name);
            $('.log_list_item_container').each(function() {
                $(this).removeClass('log_list_active');
            });
            var item = $(this).closest('.log_list_item_container');
            jQuery.ajax({
                type: 'get',
                url: url,
                beforeSend: function() {
                    loadingDiv(div);
                },
                complete: function() {
                    removeLoading(div);
                },
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    div.html(json);
                    item.addClass('log_list_active');
                }
            });
        })

        /**
         * Search in logs
         */
        .on('click', '.search_log', function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var name = $(this).attr('id');
            var url = form.attr('action');
            var div = $('.log_content_container#' + name);
            jQuery.ajax({
                type: 'get',
                url: url,
                data: form.serializeArray(),
                beforeSend: function() {
                    loadingDiv(div);
                },
                complete: function() {
                    removeLoading(div);
                },
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    div.html(json);
                }
            });
        })

        /**
         * Delete  log file
         */
        .on('click', '.delete_log', function(e) {
            e.preventDefault();
            var div = $(this).closest('.log_container').parent();
            var url = $(this).attr('href');
            jQuery.ajax({
                type: 'get',
                url: url,
                beforeSend: function() {
                    loadingDiv(div);
                },
                complete: function() {
                    removeLoading(div);
                },
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json.status == true) {
                        div.html(json.content);
                    }
                }
            });
        })

        /**
         * Delete  log file
         */
        .on('click', '.show_log_manager', function(e) {
            e.preventDefault();
            var name = $(this).attr('id');
            var div = $('.log_target_container#' + name);
            var url = $(this).attr('href');
            if (!div.is(':visible')) {

                jQuery.ajax({
                    type: 'get',
                    url: url,
                    beforeSend: function () {
                        loadingDiv(div);
                    },
                    complete: function () {
                        removeLoading(div);
                    },
                    success: function (data) {
                        var json = jQuery.parseJSON(data);
                        div.html(json).toggle();
                    }
                });
            } else {
                div.toggle();
            }
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Sessions
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Add a session/presentation type
        .on('click','.type_add',function (e) {
            e.preventDefault();
            var classname = $(this).attr('data-class');
            var typename = $('input#new_'+classname+'_type').val();
            if ($(this).hasClass('wrongField')) {
                $(this).removeClass('wrongField');
            }
            if (typename.length == 0) {
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
                        showfeedback("<span class='sys_msg warning'>We could not add this new category","feedback_"+classname);
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
                        $('.type_list#'+classname).html(result.msg);
                    } else {
                        validsubmitform( $('.type_list#'+classname), data);
                        //$('.type_list#'+classname).html(result.msg);
                    }
                }
            });
        })

        // Change default session type
        .on('change','.session_type_default',function (e) {
            e.preventDefault();
            var div = $('#session_type');
            var type = $(this).val();
            var data = {session_type_default: type};
            processAjax(div, data, null, "php/form.php");
        })

        .on('change', '.repeated_session', function(e) {
            var val = $(this).val();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            if (val == 1) {
                form.find('.settings_hidden').fadeIn();
            } else {
                form.find('.settings_hidden').fadeOut();
            }
        })

        // Select session to show
        .on('change','.selectSession',function (e) {
            e.preventDefault();
            var nbsession = $(this).val();
            var status = ($(this).attr('data-status').length) ? $(this).data('status'):'admin';
            var view = ($(this).data('view') == undefined) ? 'simple' : $(this).data('view');
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
            var presid = container.attr('id');
            var date = $(this).closest('.session_div').data('id');
            var data = {
                modSpeaker: speaker,
                previous: previous,
                presid: presid,
                date:date
            };
            processAjax($(this).closest('.session_div'), data, null, "php/form.php");
        })

        // Modify session time
        .on('change','.mod_session',function (e) {
            e.preventDefault();
            var prop = $(this).attr('name');
            var value = $(this).val();
            var sessionDiv = $(this).closest('.session_div');
            var sessionID = sessionDiv.data('id');
            var data = {modSession: true, session: sessionID, prop: prop, value: value};
            processAjax(sessionDiv, data, null, "php/form.php");
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
            var data = $(this).data();
            data['id'] = $(this).val();
            get_submission_form(data);
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
         .on('change', 'select#type', function (e) {
            e.preventDefault();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            var type = $(this).val();
            var callback = function(result) {
                $('.form_lower_container').html(result);
                tinyMCE.remove();
                window.tinymce.dom.Event.domLoaded = true;
                tinymcesetup();
            };
            var data = {getFormContent: type};
            processAjax($('.form_lower_container'), data, callback, "php/form.php");

         })

        // Submit a presentation
        .on('click','.submit_pres',function (e) {
            e.preventDefault();
            var operation = $(this).attr('name');
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            var type = $("select#type").val();
            // Check if the form has been fully completed
            if (!checkform(form)) { return false;}

            // Check if a data has been selected (except for suggestion)
            if (operation !== "suggest") {
                var date = $("input.datepicker").val();
                if (date.length == 0) {
                    showfeedback('<p id="warning">You must choose a date!</p>');
                    form.find("input.datepicker").focus();
                    return false;
                }
            }

            // Check if files have been uploaded and attach them to this presentation
            var uploadInput = $('input.upl_link');
            if (uploadInput[0]) {
                var links = [];
                uploadInput.each(function () {
                    var link = $(this).val();
                    links.push(link);
                });
                links = links.join(',');
                form.append("<input type='hidden' name='link' value='"+links+"'>");
            }

            // Submit presentation
            var data = form.serializeArray();
            var callback = function (result) {
                $('section#submission_form, .modal_section#submission_form').empty();
                if (result.status === true) {
                    close_modal();
                }
            };

            // Find tinyMCE textarea and gets their contentf
            var tinyMCE_el = form.find('.tinymce');
            if (tinyMCE_el.length > 0 && tinyMCE_el !== undefined) {
                tinyMCE_el.each(function() {
                    var id = $(this).attr('id');
                    var input_name = $(this).attr('name');
                    var content = tinyMCE.get(id).getContent();
                    data = modArray(data, input_name, content);
                })
            }
            var div = $(this).closest('.form_container');
            processAjax(div, data, callback, "php/form.php");
            e.stopImmediatePropagation();
        })

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

        .on('click', '.logout_button', function(e) {
            $('.logoutWarning').fadeOut(200).toggle();
        })

        .on('click', '.extend_session', function(e) {
            extend_session();
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Submission triggers
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Show publication information on click
        .on('click','.show_submission_details',function (e) {
            e.preventDefault();
            show_submission_details($(this).data());
        })

        // Choose a wish
        .on('click','.get_submission_form',function (e) {
            e.preventDefault();
            get_submission_form($(this).data());
        })

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
            if (operation == 'wishpick') {
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

        .on('click', '.delete', function(e) {
            e.preventDefault();
            var el = $(this);
            confirmation_box(el, 'Are you sure you want to delete this item?', 'Delete', function () {
                var data = el.data();
                data['delete'] = true;
                jQuery.ajax({
                    url: 'php/form.php',
                    type: 'post',
                    data: data,
                    success: function(ajax) {
                        var result = jQuery.parseJSON(ajax);
                        console.log(result);
                    }
                });
            });
        })

        .on('click', '.confirm_delete', function(e) {
            e.preventDefault();
            var formid = $(".modal_section#delete_confirmation");
            var el_id = $('input[name="el_id"]').val();
            var url_params = $('input[name="url"]').val();
            var callback = function(result) {
                if (result.status === true) {
                    close_modal();
                    $('.el_to_del#' + el_id).remove();
                }
                return false;
            };
            var data = {'delete_item': true, 'params': url_params};
            processAjax(formid, data, callback, 'php/form.php');
        })

		// Show publication modification form
        .on('click','.modify_ref',function (e) {
            e.preventDefault();
            get_submission_form($(this).data());
        })

		// Show publication deletion confirmation
        .on('click',".delete_ref",function (e) {
            e.preventDefault();
            var id_pres = $(this).data("id");
            var controller = $(this).data('controller');
            show_section('pub_delete');
            $("#pub_delete").append('' +
                '<input type=hidden name="del_pub" value="' + id_pres + '"/>' +
                    '<input type=hidden name="controller" value="' + controller + '"/>' +
                '');
        })

        // Going back to publication
        .on('click',".pub_back_btn",function (e) {
            e.preventDefault();
            show_section('submission_form');
        })

        // Confirm delete publication
        .on('click',"#confirm_pubdel",function (e) {
            e.preventDefault();
            var id_pres = $("input[name='del_pub']").val();

            var data = {del_pub:id_pres, controller: $("input[name='controller']").val()};
            var el = $('.modal_section#pub_delete');
            var callback = function (result) {
                if (result.status === true) {
                    close_modal('.modalContainer');
                    $('#' + id_pres).remove();
                }
            };
            processAjax(el, data, callback, "php/form.php");
        })

        // Dialog change password
        .on('click',".modal_trigger_changepw",function (e) {
            e.preventDefault();
            show_section('user_changepw');
        })

        // Going back to Login Forms
        .on('click',".back_btn",function (e) {
            e.preventDefault();
            show_section('user_login');
            return false;
        })

        // Go to sign up form
        .on('click','.gotoregister',function (e) {
            e.preventDefault();
            show_section('user_register');
        })

        // Delete user account confirmation form
        .on('click',".confirmdeleteuser",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function (result) {
                if (result.status === true) {
                    logout();
                }
            };
            processForm(form, callback);
            e.stopImmediatePropagation();
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
            processForm(form, callback);
            e.stopImmediatePropagation();
        })

        // Sign Up Form
        .on('click', ".register",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function (result) {
                if (result.status === true) {
                    close_modal('.modalContainer');
                }
            };
            processForm(form, callback);
            e.stopImmediatePropagation();
        });
});
