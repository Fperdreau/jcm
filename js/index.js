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
 * @param formel: ID of the form
 * @param idpress: ID of the presentation
 * @param type: form's type (submit, modify, suggest)
 * @param date: presentation's date
 * @param prestype
 */
var showpubform = function (formel, idpress, type, date, prestype) {
    if (idpress === undefined) {idpress = false; }
    if (type === undefined) {type = "submit"; }
    if (date === undefined) {date = false; }
    if (prestype === undefined) {prestype = false; }
    var data = {
        getpubform: idpress,
        type: type,
        date: date,
        prestype: prestype
    };
    // First we remove any existing submission form
    var callback = function (result) {
        formel
            .html(result)
            .fadeIn(200)
            .find('textarea').html(result.content);
        tinyMCE.remove();
        window.tinymce.dom.Event.domLoaded = true;
        tinymcesetup();

    };
    processAjax(formel, data, callback, "php/form.php");

    // Load JCM calendar
    loadCalendarSessions();

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

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 jQuery DataPicker
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
var selected = new Date().getTime();

/**
 * Initialize jQuery-UI calendar
 * @param data_availability: associative array providing journal club sessions and their information
 */
var inititdatepicker = function (data_availability) {

    $('#datepicker').datepicker({
        defaultDate: selected,
        firstDay: 1,
        dateFormat: 'yy-mm-dd',
        inline: true,
        showOtherMonths: true,
        beforeShowDay: function(date) {
            return renderCalendarCallback(date, data_availability);
        }
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

function renderCalendarCallback(date, data) {
    var day = date.getDay();
    var days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
    var cur_date = $.datepicker.formatDate('dd-mm-yy', date);
    if (days[day] === data.jc_day) {
        var css = "activeday";
        var text = "";
        var find = $.inArray(cur_date, data.jc_day);
        var status = data.status[find];
        var clickable = (status !== 'none');

        // If the date is booked
        if (find > -1) {
            var type = jcdays.sessiontype[find];
            var rem = jcdays.max_nb_session - jcdays.nb[find]; // Number of presentations available that day
            text = type + ": (" + rem + " presentation(s) available)";
            if (status === 'Free') {
                css = "jcday " + css;
            } else if (status === 'Booked') {
                css = "jcday_rem " + css;
            } else {
                css = "bookedday " + css;
                text = type + ": Booked out";
            }
            css = "bookedday " + css;
        } else {
            css = "jcday " + css;
            text = data.max_nb_session + " presentation(s) available";
        }

        var isAvailable = $.inArray(cur_date, data.Availability);
        if (isAvailable > -1) {
            css = "not_available ";
            text = "Not available";
        }

        var isAssigned = $.inArray(cur_date, data.Assignments);
        if (isAssigned > -1) {
            css = "assigned ";
            text = "You are presenting this day";
        }

        return [clickable, css, text];

    } else if (days[day] !== data.jc_day) {
        return [false, "", "Not a journal club day"];
    }
}

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Logout
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
/**
 * Log out the user and trigger a modal window informing the user he/she has been logged out
 */
var logout = function () {
    $('.warningmsg').remove();
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {logout: true},
        success: function (data) {
            var json = jQuery.parseJSON(data);
            $('.mainbody').append("<div class='logoutWarning'>You have been logged out!</div>");
            $('.logoutWarning').fadeIn(200);
            setTimeout(function () {
                $('.logoutWarning')
                    .fadeOut(200)
                    .empty()
                    .hide();
                window.location = json;
            }, 3000);
        }
    });
};

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Modal windows
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
var modalpubform = $('.modal_section#submission_form');

/**
 * Display presentation's information in a modal window
 * @param idpress
 * @param formel
 */
var displaypub = function (idpress, formel) {
    idpress = (idpress === undefined) ? false : idpress;
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: false,
        data: {
            show_pub: idpress
        },
        success: function (data) {
            var result = jQuery.parseJSON(data);
            formel
                .hide()
                .html(result)
                .fadeIn(200);

            // Load JCM calendar
            loadCalendarSessions();
        }
    });
};

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

$(document).ready(function () {
    var previous;

    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Main body
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    $('.mainbody')

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

        .on('mouseenter', '.submitMenuSection', function(e) {
            e.preventDefault();
            var section = $(this).find('.icon_container');
            var text = section.find('.text');
            var icon = section.find('.icon');
            section.animate({
                left: '-=' + icon.outerWidth() + 'px'
            }, 200, function() {
                // Animation complete.
            });
            text.css({visibility: 'visible'});
        })

        .on('mouseleave', '.submitMenuSection', function(e) {
            e.preventDefault();
            var section = $(this).find('.icon_container');
            var text = section.find('.text');
            var icon = section.find('.icon');
            section.animate({
                left: '+=' + icon.outerWidth() + 'px'
            }, 200, function() {
                // Animation complete.
            });
            text.css({visibility: 'hidden'});
        })

        // Display submenu
        .on('click','.submenu_trigger',function (e) {
            e.preventDefault();
            var menuEl = $(this).parent('li');
            var absPos = menuEl.offset();
            var position = menuEl.position();
            var width = menuEl.outerWidth();
            var height = menuEl.outerHeight();
            var id = $(this).attr('id');
            var submenu;
            if ($(this).closest('div').hasClass('sideMenu')) {
                submenu = $(".sideMenu nav.submenu#"+id);
                submenu.toggle(200);
            } else {
                submenu = $(".topnav nav.submenu#"+id);
                var submenuWidth = realWidth(submenu); // Get submenu width
                var horizontal;
                if (absPos.left+width+submenuWidth < $(window).width()) {
                    horizontal = position.left;
                } else {
                    horizontal = position.left-submenuWidth;
                }
                submenu
                    .css({
                        'left':horizontal+"px",
                        'top':position.top+height+"px"
                    })
                    .toggle(200);
            }

        })

        // Main menu sections
        .on('click',".menu-section",function (e) {
            e.preventDefault();
            e.stopPropagation();

            $(".menu-section").removeClass("activepage");
            $(this).addClass("activepage");

            if ($(this).is('[id]')) {
                var pagetoload = $(this).attr("id");
                var param = ($(this).is('[data-param]'))? $(this).attr('data-param'):false;
                getPage(pagetoload,param);
                $('.submenu, .dropdown').hide();
            }

            var sideMenu = $('.sideMenu');
            if (sideMenu.is(':visible')) {
                sideMenu.animate({width:"toggle"});
            }
        })

        // Hide dropdown menus when not clicked
        .on('click',function (e) {
            var nav = $("nav");
            var sideMenu = $('.sideMenu');
            if (!$('#float_menu').is(e.target)&& $('#float_menu').has(e.target).length === 0) {
                if (!nav.is(e.target) && nav.has(e.target).length === 0) {
                    $('.submenu').hide();
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
                    'recipients before sending your email by clicking on the "+" button!</p>');
                return true;
            }

            // Check if form has been filled in properly
            if (!checkform(form)) {return false;}

            var data = form.serializeArray();
            var content = tinyMCE.get('spec_msg').getContent();
            var attachments = [];
            form.find('input.upl_link').each(function() {
                attachments.push($(this).val());
            });
            attachments = attachments.join(',');
            data = modArray(data, 'spec_msg', content);
            data = modArray(data, 'attachments', attachments);

            processAjax(el, data, null, "php/form.php");
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
            processAjax('.postcontent', data, callback, "php/form.php");
        })

        // Add a news to the homepage
        .on('click','.submit_post',function (e) {
            e.preventDefault();
            var form = $(this).closest('#post_form');
            if (!checkform(form)) {return false;}
            var callback = function (result) {
                if (result.status === true) {
                    showpostform(false);
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
        // Add a session/presentation type
        .on('click','.type_add',function (e) {
            var classname = $(this).attr('data-class');
            var typename = $('input#new_'+classname+'_type').val();

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    add_type: classname,
                    typename: typename},
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
            var typename = $(this).attr('data-type');
            var classname = $(this).attr('data-class');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    del_type: classname,
                    typename: typename},
                success: function (data) {
                    var result = jQuery.parseJSON(data);
                    if (result !== false) {
                        $('.type_list#'+classname).html(result);
                    }
                }
            });
        })

        // Change default session type
        .on('change','.session_type_default',function (e) {
            e.preventDefault();
            var div = $('#session_type');
            var type = $(this).val();
            var data = {session_type_default:type};
            processAjax(div, data, null, "php/form.php");
        })

        // Select session to show
        .on('change','.selectSession',function (e) {
            e.preventDefault();
            var nbsession = $(this).val();
            var status = ($(this).attr('data-status').length) ? $(this).data('status'):'admin';
            var data = {show_session: nbsession, status: status};
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
            processAjax($('body'), data, null, "php/form.php");
        })

        // Modify session type
        .on('change','.mod_session',function (e) {
            e.preventDefault();
            var prop = $(this).attr('name');
            var value = $(this).val();
            var sessionDiv = $(this).closest('.session_div');
            var sessionID = sessionDiv.data('id');
            var data = {modSession: true, session: sessionID, prop: prop, value: value};
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
            var presid = $(this).val();
            var form = $('.submission');
            showpubform(form, presid, 'submit');
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
         .on('change','select#type',function (e) {
            e.preventDefault();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();

            var guestField = form.find('#guest');
            var titleField = form.find("input[name='title']").parent('.formcontrol');
            var authorsField = form.find("input[name='authors']").parent('.formcontrol');
            var type = $(this).val();
            guestField.prop('required', false);

             // Show/Hide Guest field depending on the selected presentation type
            if (type === "guest") {
                guestField
                    .prop('required', true)
                    .fadeIn();
            } else {
                guestField
                    .prop('required', false)
                    .hide();
            }

             // Show/Hide Guest field depending on the selected presentation type
             if (type == "minute") {
                 titleField
                     .prop('required', true)
                     .hide();
                 authorsField
                     .prop('required', true)
                     .hide();
             } else {
                 titleField
                     .prop('required', false)
                     .fadeIn();
                 authorsField
                     .prop('required', false)
                     .fadeIn();
             }

         })

        // Submit a presentation
        .on('click','.submit_pres',function (e) {
            e.preventDefault();
            var operation = $(this).attr('name');
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            var type = $("select#type").val();
            // Check if the form has been fully completed
            if (!checkform(form)) { return false;}

            // Check if a data has been selected (except for wishes)
            if (operation !== "suggest") {
                var date = $("input#datepicker").val();
                if ((date === "0000-00-00" || date === "") && type !== "wishlist") {
                    showfeedback('<p id="warning">You must choose a date!</p>');
                    form.find("input#datepicker").focus();
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

            // Find tinyMCE textarea and gets their content
            var tinyMCE_el = form.find('.tinymce');
            if (tinyMCE_el.length > 0 && tinyMCE_el !== undefined) {
                tinyMCE_el.each(function() {
                    var id = $(this).attr('id');
                    var input_name = $(this).attr('name');
                    var content = tinyMCE.get(id).getContent();
                    data = modArray(data, input_name, content);
                })
            }

            processAjax(form, data, callback, "php/form.php");
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Modal triggers
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Log out
        .on('click',"#logout",function (e) {
            e.preventDefault();
            logout();
        })

        // Show publication information on click
        .on('click','#modal_trigger_pubcontainer',function (e) {
            e.preventDefault();
            var id_pres = $(this).attr('data-id');
            displaypub(id_pres, modalpubform);
        })

        // Choose a wish
        .on('click','#modal_trigger_pubmod',function (e) {
            e.preventDefault();
            var id_pres = $(this).data('id');
            var date = $(this).data('date');
            showpubform(modalpubform, id_pres, 'submit', date);
        })

        .on('click','#modal_trigger_newpub',function (e) {
            e.preventDefault();
            var type = $(this).data('type');
            showpubform(modalpubform, false, type);
        })
    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Modal Window
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
		// Show publication modification form
        .on('click','.modify_ref',function (e) {
            e.preventDefault();
            var id_pres = $(this).attr("data-id");
            showpubform(modalpubform, id_pres, 'submit');
        })

		// Show publication deletion confirmation
        .on('click',".delete_ref",function (e) {
            e.preventDefault();
            var id_pres = $(this).attr("data-id");
            show_section('pub_delete');
            $("#pub_delete").append('<input type=hidden id="del_pub" value="' + id_pres + '"/>');
        })

        // Going back to publication
        .on('click',".pub_back_btn",function (e) {
            e.preventDefault();
            show_section('submission_form');
        })

        // Confirm delete publication
        .on('click',"#confirm_pubdel",function (e) {
            e.preventDefault();
            var id_pres = $("input#del_pub").val();
            var data = {del_pub:id_pres};
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
        })

        // Login form
        .on('click',".login",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function (result) {
                if (result.status === true) {
                    location.reload();
                }
            };
            processForm(form, callback);
        })

        // Sign Up Form
        .on('click',".register",function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function (result) {
                if (result.status === true) {
                    close_modal('.modalContainer');
                }
            };
            processForm(form, callback);
        });
});
