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
var showpubform = function(formel,idpress,type,date,prestype) {
    if (idpress == undefined) {idpress = false;}
    if (type == undefined) {type = "submit";}
    if (date == undefined) {date = false;}
    if (prestype == undefined) {prestype = false;}
    var data = {
        getpubform: idpress,
            type: type,
            date: date,
            prestype: prestype
    };
    // First we remove any existing submission form
    var callback = function(result) {
        formel
            .html(result)
            .fadeIn(200);

    };
    processAjax(formel,data,callback);

};

/**
 * Display form to post a news
 * @param postid
 */
var showpostform = function(postid) {
    var el = $('.postcontent');
    var data = {post_show: true,postid: postid};
    var callback = function(result) {
        var txtarea = "<textarea name='content' id='post_content' class='tinymce'>"+result.content+"</textarea>";
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
    processAjax(el,data,callback);
};

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 jQuery DataPicker
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
/**
 * Initialize jQuery-UI calendar
 * @param jcdays: associative array providing journal club sessions and their information
 * @param selected: Currently selected day
 */
var inititdatepicker = function(jcdays,selected) {

    $('#datepicker').datepicker({
        defaultDate: selected,
        firstDay: 1,
        dateFormat: 'yy-mm-dd',
        inline: true,
        showOtherMonths: true,
        beforeShowDay: function(date) {
            var day = date.getDay();
            var days = new Array("sunday","monday","tuesday","wednesday","thursday","friday","saturday");
            var cur_date = $.datepicker.formatDate('dd-mm-yy',date);
            var today = new Date();
            if (days[day] == jcdays.jc_day) {
                //var css = (date >= today) ? "activeday":"pastday";
                var css = "activeday";
                var find = $.inArray(cur_date,jcdays.booked);
                var status = jcdays.status[find];
                //var clickable = (date >= today && status != 'none' && status != 'Booked out');
                var clickable = (status != 'none');
                // If the date is booked
                if (find > -1) {
                    var type = jcdays.sessiontype[find];
                    var rem = jcdays.max_nb_session-jcdays.nb[find]; // Number of presentations available that day
                    var msg = type+": ("+rem+" presentation(s) available)";
                    if (status == 'Free') {
                        return [clickable,"jcday "+css,msg];
                    } else if (status == 'Booked') {
                        return [clickable,"jcday_rem "+css,msg];
                    } else {
                        return [clickable,"bookedday "+css,type+": Booked out"];
                    }
                } else {
                    return [clickable,"jcday "+css,jcdays.max_nb_session+" presentation(s) available"];
                }
            } else if (days[day] !== jcdays.jc_day) {
                return [false,"","Not a journal club day"];
            }
        }
    });
};

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Logout
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
/**
 * Log out the user and trigger a modal window informing the user he/she has been logged out
 */
var logout = function() {
    $('.warningmsg').remove();
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {logout: true},
        success: function() {
            $('.mainbody').append("<div class='logoutWarning'>You have been logged out!</div>");
            $('.logoutWarning').fadeIn(200);
            setTimeout(function() {
                $('.logoutWarning')
                    .fadeOut(200)
                    .empty()
                    .hide();
                location.reload();
            },3000);
        }
    });
};

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Modal windows
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
var modalpubform = $('.modal_section#submission_form');

/**
 * Close modal window
 * @param modal_id
 */
var close_modal = function(modal_id) {
    $("#lean_overlay").fadeOut(200);
    $(modal_id).css({"display":"none"});
    $("modal_section#submission_form").empty();
};

/**
 * Show the targeted modal section and hide the others
 * @param sectionid
 */
var showmodal = function(sectionid) {
    var title = $(".modal_section#"+sectionid).attr('data-title');
    $(".popupHeader").text(title);
    $('.modal_section').each(function() {
        var thisid = $(this).attr('id');
        if (thisid === sectionid) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
};

/**
 * Automatically show login window on start (if user is not already logged in)
 */
function showLogin() {
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {isLogged: true},
        success: function(data) {
            var json = jQuery.parseJSON(data);
            if (json === false) {
                $('.modal_trigger#user_login')
                    .leanModal({top : 50, overlay : 0.6, closeButton: ".modal_close" })
                    .click();
            }
        }
    });
}

/**
 * Display presentation's information in a modal window
 * @param idpress
 * @param formel
 */
var displaypub = function(idpress,formel) {
    idpress = (idpress == undefined) ? false:idpress;
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: false,
        data: {
            show_pub: idpress
        },
        success: function(data){
            var result = jQuery.parseJSON(data);
            formel
                .hide()
                .html(result)
                .fadeIn(200);
        }
    });
};

/**
 * Get width of hidden objects (useful to get width of submenus)
 * @param obj (DOM element)
 * @returns {*}
 */
function realWidth(obj){
    var clone = obj.clone();
    clone.css("visibility","hidden");
    $('body').append(clone);
    var width = clone.outerWidth();
    clone.remove();
    return width;
}

$( document ).ready(function() {

    $('body').ready(function() {
        showLogin();
    });

    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Main body
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    $('.mainbody')

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Header menu/Sub-menu
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Dropdown menu
        .on('click','#float_menu',function() {
            var position = $(this).position();
            var height = $(this).outerHeight();
            $('.sideMenu')
                .css({
                    'top':height+"px",
                    'left':position.left+"px"
                })
                .animate({width:'toggle'});
        })

        // Display submenu
        .on('click','.submenu_trigger',function(e) {
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
        .on('click',".menu-section",function(e){
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
        .on('click',function(e) {
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
        .on('mouseenter','#core, .submission',function(e) {
            e.preventDefault();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {get_calendar_param: true},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var selected_date = $('input[type="date"]').val();
                    inititdatepicker(result,selected_date);
                }
            });
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         User Profile
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
		// Send a verification email to the user if a change of password is requested
        .on('click',".change_pwd",function(e){
            e.preventDefault();
            var form = $(this).closest('form');
            var email = $(this).attr("id");
            var data = {change_pw: true, email: email};
            processAjax(form,data);
        })

		// Password change form (email + new password)
        .on('click',".conf_changepw",function(e){
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            if (!checkform(form)) {return false;}
            var data = form.serialize();
            var callback = function(result) {
                if (result.status == true) {
                    setTimeout(logout,2000);
                }
            };
            processAjax(form,data,callback);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin tools
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // User management tool: sort users
		.on('click','.user_select',function(e) {
            e.preventDefault();
            var filter = $(this).data('filter');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    user_select: filter
                    },
                success: function(data){
                    var result = jQuery.parseJSON(data);
					$('#user_list').html(result);
                }
            });
            return false;
        })

        // User Management tool: Modify user status
        .on('change','.modify_status',function(e) {
            e.preventDefault();
            var div = $('#user_list');
            var username = $(this).attr("data-user");
            var option = $(this).val();
            var data = {modify_status: true,username: username,option: option};
            var callback = function(result) {
                setTimeout(function() {
                    $('#user_list').html(result.content);
                },2000);
            };
            processAjax(div,data,callback);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Mailing
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
		// Send an email to the mailing list
        .on('click','.mailing_send',function(e) {
            e.preventDefault();
            var form = $(this).closest('#mailing_send');
            if (!checkform(form)) {return false;}
            var data = form.serialize();
            processAjax(form,data);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - News
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Select news to modify
        .on('change','.select_post',function(e) {
            e.preventDefault();
            var postid = $(this).val();
            showpostform(postid);
        })

        // Add a new post
        .on('click','.post_new',function(e) {
            e.preventDefault();
            showpostform(false);
        })

        // Delete a post
        .on('click','.post_del',function(e) {
            e.preventDefault();
            var postid = $(this).attr('data-id');
            var data = {post_del: true, postid: postid};
            var callback = function(result) {
                if (result.status === true) {
                    showpostform(false);
                }
            };
            processAjax('.postcontent',data,callback);
        })

        // Add a news to the homepage
        .on('click','.submit_post',function(e) {
            e.preventDefault();
            var form = $(this).closest('#post_form');
            if (!checkform(form)) {return false;}
            var callback = function(result) {
                if (result.status === true) {
                    showpostform(false);
                }
            };
            var data = form.serializeArray();
            console.log(data); return false;
            processAjax(form,data,callback);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin - Sessions
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Add a session/presentation type
        .on('click','.type_add',function(e) {
            var classname = $(this).attr('data-class');
            var typename = $('input#new_'+classname+'_type').val();

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    add_type: classname,
                    typename: typename},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result !== false) {
                        $('.type_list#'+classname).html(result);
                        $('input#new_'+classname+'_type').empty();
                    } else {
                        showfeedback("<span id='warning'>We could not add this new category","feedback_"+classname);
                    }
                }
            });
        })

        // Delete a session/presentation type
        .on('click','.type_del',function(e) {
            var typename = $(this).attr('data-type');
            var classname = $(this).attr('data-class');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    del_type: classname,
                    typename: typename},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result !== false) {
                        $('.type_list#'+classname).html(result);
                    }
                }
            });
        })

        // Change default session type
        .on('change','.session_type_default',function(e) {
            e.preventDefault();
            var div = $('#session_type');
            var type = $(this).val();
            var data = {session_type_default:type};
            processAjax(div,data);
        })

        // Select session to show
        .on('change','.selectSession',function(e) {
            var nbsession = $(this).val();
            var status = ($(this).attr('data-status').length) ? $(this).data('status'):'admin';
            var data = {show_session: nbsession, status: status};
            var div = $('#sessionlist');
            var callback = function(result) {
                $('#sessionlist')
                    .html(result)
                    .fadeIn(200);
            };
            processAjax(div,data,callback);
        })

        // Modify speaker
        .on('change','.modSpeaker',function(e) {
            var speaker = $(this).val();
            var container = $(this).closest('.pres_container');
            var presid = container.attr('id');
            var data = {modSpeaker: speaker, presid: presid};
            processAjax(container,data);
        })

        // Modify session type
        .on('change','.mod_session',function(e) {
            e.preventDefault();
            var prop = $(this).attr('name');
            var value = $(this).val();
            var sessionDiv = $(this).closest('.session_div');
            var sessionID = sessionDiv.data('id');
            var data = {modSession: true, session: sessionID, prop: prop, value: value};
            processAjax(sessionDiv,data);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Publication lists (Archives/user publications)
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Sort publications by years
		.on('change','.archive_select',function(e) {
            e.preventDefault();
            var year = $(this).val();
            var data = {select_year: year};
            var callback = function(result) {
                $('#archives_list').html(result);
            };
            var div = $('#archives_list');
            processAjax(div,data,callback);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Presentation submission
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Select a wish
        .on('change','#select_wish',function(e) {
            e.preventDefault();
            var presid = $(this).val();
            var form = $('.submission');
            showpubform(form,presid,'submit');
         })

        // Show download list
        .on('click','.dl_btn',function() {
            $(".dlmenu").toggle();
        })

         // Select submission type
         .on('change','select#type',function(e) {
            e.preventDefault();
            var guestField = $('.submission #guest');
            var type = $(this).val();
            guestField.prop('required',false);

            if (type == "guest") {
                guestField
                    .prop('required',true)
                    .fadeIn();
            } else {
                guestField
                    .prop('required',false)
                    .hide();
            }
         })

        // Submit a presentation
        .on('click','.submit_pres',function(e) {
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
                    $("input#datepicker").focus();
                    return false;
                }
            }

            // Check if files have been uploaded and attach them to this presentation
            var uploadInput = $('input.upl_link');
            if (uploadInput[0]) {
                var links = new Array();
                uploadInput.each(function(){
                    var link = $(this).val();
                    links.push(link);
                });
                links = links.join(',');
                form.append("<input type='hidden' name='link' value='"+links+"'>");
            }

            // Submit presentation
            var data = form.serialize();
            var callback = function(result) {
                var subform = $('section#submission_form, .modal_section#submission_form');
                if (result.status == true) {
                    showpubform(subform,false);
                }
            };
            processAjax(form,data,callback);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         FORMS
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        .on('click','.processform',function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            processForm(form);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Modal triggers
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
		// Trigger modal dialog box for log in/sign up
        .on('mouseover',"a[rel*=leanModal]",function(e) {
            e.preventDefault();
            $(this).leanModal({top : 50, overlay : 0.6, closeButton: ".modal_close" });
        })

        // Dialog log in
        .on('click',".modal_trigger",function(e){
            e.preventDefault();
            var section  = $(this).attr('id');
            showmodal(section);
        })

        // Log out
        .on('click',"#logout",function(e){
            e.preventDefault();
            logout();
        })

        // Show publication information on click
        .on('click','#modal_trigger_pubcontainer',function(e){
            e.preventDefault();
            var id_pres = $(this).attr('data-id');
            showmodal('submission_form');
            displaypub(id_pres,modalpubform);
        })

        // Choose a wish
        .on('click','#modal_trigger_pubmod',function(e){
            e.preventDefault();
            var id_pres = $(this).data('id');
            var date = $(this).data('date');
            showmodal('submission_form');
            showpubform(modalpubform,id_pres,'submit',date);
        })

        .on('click','#modal_trigger_newpub',function(e){
            e.preventDefault();
            var type = $(this).data('type');
            showmodal('submission_form');
            showpubform(modalpubform,false,type);
        })
    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Modal Window
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
		// Show publication modification form
        .on('click','.modify_ref',function(e) {
            e.preventDefault();
            var id_pres = $(this).attr("data-id");
            showmodal('submission_form');
            showpubform(modalpubform,id_pres,'submit');
        })

		// Show publication deletion confirmation
        .on('click',".delete_ref",function(e){
            e.preventDefault();
            var id_pres = $(this).attr("data-id");
            showmodal('pub_delete');
            $("#pub_delete").append('<input type=hidden id="del_pub" value="' + id_pres + '"/>');
        })

        // Going back to publication
        .on('click',".pub_back_btn",function(){
            showmodal('submission_form');
        })

        // Confirm delete publication
        .on('click',"#confirm_pubdel",function(e) {
            e.preventDefault();
            var id_pres = $("input#del_pub").val();
            var data = {del_pub:id_pres};
            var el = $('.modal_section#pub_delete');
            var callback = function(result) {
                if (result.status == true) {
                    close_modal('.modalContainer');
                    $('#' + id_pres).remove();
                }
            };
            processAjax(el,data,callback);
        })

        // Dialog change password
        .on('click',".modal_trigger_changepw",function(e){
            e.preventDefault();
            showmodal('user_changepw');
        })

        // Going back to Login Forms
        .on('click',".back_btn",function(e){
            e.preventDefault();
            showmodal('user_login');
            return false;
        })

        // Go to sign up form
        .on('click','.gotoregister',function(e) {
            e.preventDefault();
            showmodal('user_register');
        })

        // Delete user account confirmation form
        .on('click',"#confirmdeleteuser",function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function(result) {
                if (result.status === true) {
                    logout();
                    window.location.href = "index.php?page=home";
                    location.reload();
                }
            };
            processForm(form,callback);
        })

        // Login form
        .on('click',".login",function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function(result) {
                if (result.status === true) {
                    location.reload();
                }
            };
            processForm(form,callback);
        })

        // Sign Up Form
        .on('click',".register",function(e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            var callback = function(result) {
                if (result.status === true) {
                    close_modal('.modalContainer');
                }
            };
            processForm(form,callback);
        });
});
