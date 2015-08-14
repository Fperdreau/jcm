/**
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

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 General functions
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Show publication form
var showpubform = function(formel,idpress,type,date,prestype) {
    console.log(idpress);
    if (idpress == undefined) {idpress = false;}
    if (type == undefined) {type = "submit";}
    if (date == undefined) {date = false;}
    if (prestype == undefined) {prestype = false;}

    // First we remove any existing submission form
    $('.submission').remove();
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: false,
        data: {
            getpubform: idpress,
            type: type,
            date: date,
            prestype: prestype
        },
        success: function(data){
            var result = jQuery.parseJSON(data);
            formel
                .hide()
                .html(result)
                .show();
        }
    });
};

// Display presentation information in a modal window
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

// Process submitted form
var processform = function(formid,feedbackid) {
    var feedbackdiv = (typeof feedbackid === "undefined") ? feedbackdiv = "feedback": feedbackdiv=feedbackid;

    var data = $("#"+formid).serialize();
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: true,
        data: data,
        beforeSend: function() {
            loadingDiv("#"+formid);
        },
        complete: function() {
            removeLoading("#"+formid);
        },
        success: function(data){
            var result = jQuery.parseJSON(data);
            showfeedback(result,feedbackdiv);
        }
    });
};

// Show feedback message and replace the submitted form by an empty form
var validsubmitform = function(form,text) {
    $(form)
        .hide()
        .html(text)
        .fadeIn(200);
};

// Check for empty input fields
var checkform = function(formid) {
    var valid = true;
    $('#'+formid+' input,select').each(function () {
        if ($.trim($(this).val()).length === 0){
            $(this).focus();
            showfeedback('<p id="warning">This field is required</p>');
            valid = false;
            return false;
        }
    });
    return valid;
};

// Check email validity
var checkemail = function(email) {
    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    return pattern.test(email);
};

//Show feedback
var showfeedback = function(message,selector) {
    var el = (typeof selector === "undefined") ? ".feedback":".feedback#"+selector;

    $(el)
        .html(message)
        .fadeIn();
    setTimeout(function() {
       $(el)
           .empty();
    },3000);
    return false;
};

// send verification email after signing up
var send_verifmail = function(email) {
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: true,
        data: {
            change_pw: true,
            email: email},
        success: function(data){
            var result = jQuery.parseJSON(data);
            if (result === "sent") {
                showfeedback('<p id="success">A verification email has been sent to your address</p>');
            } else if (result === "wrong_email") {
                showfeedback('<p id="warning">Wrong email address</p>');
            }
        }
    });
};

// initialize jQuery-UI Calendar
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

// Show post form
var showpostform = function(postid) {
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: true,
        data: {
            post_show: true,
            postid: postid},
        success: function(data){
            var result = jQuery.parseJSON(data);
            var txtarea = "<textarea name='content' id='post_content' class='tinymce'>"+result.content+"</textarea>";
            setTimeout(function() {
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
            }, 1000);

        }
    });
};

/*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
 Logout
 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
var logout = function() {
    $('.warningmsg').remove();
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {logout: true},
        success: function() {
            $('.mainbody').append("<div class='warningmsg'>You have been logged out!</div>");
            $('.warningmsg').fadeIn(200);
            setTimeout(function() {
                $('.warningmsg')
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

// Close modal window
var close_modal = function(modal_id) {
    $("#lean_overlay").fadeOut(200);
    $(modal_id).css({"display":"none"});
    $("modal_section#submission_form").empty();
};

// Show the targeted modal section and hide the others
var showmodal = function(sectionid) {
    var title = $(".modal_section#"+sectionid).attr('data-title');
    $(".header_title").text(title);
    $('.modal_section').each(function() {
        var thisid = $(this).attr('id');
        if (thisid === sectionid) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
};

// show Login on start
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
        // Main menu sections
        .on('click',".menu-section",function(){
            $(".menu-section").removeClass("activepage");
            $(this).addClass("activepage");

            if ($(this).is('[data-url]')) {
                var pagetoload = $(this).attr("data-url");
                loadpageonclick(pagetoload,false);
            }
        })

        // Sub-menu sections
        .on('click',".addmenu-section",function(){
            $(".addmenu-section").removeClass("activepage");
            $(this).addClass("activepage");

            if ($(this).is('[data-url]')) {
                var pagetoload = $(this).attr("data-url");
                if ($(this).is('[data-param]')) {
                    var dataparam = $(this).attr("data-param");
                    loadpageonclick(pagetoload,dataparam);
                } else {
                    loadpageonclick(pagetoload,false);
                }
            }
        })

        .on('click',function(event) {
            if(!$(event.target).closest('#menu_admin').length) {
                if($('.addmenu-admin').is(":visible")) {
                    $('.addmenu-admin').slideToggle();
                }
            }
            if(!$(event.target).closest('#menu_pres').length) {
                if($('.addmenu-pres').is(":visible")) {
                    $('.addmenu-pres').slideToggle();
                }
            }
        })

		// Show presentation sub-menu
        .on('click','#menu_pres',function() {
            var position = $(this).position();
            var headerHeight = $('.header').outerHeight();
            $('.addmenu-pres')
                .css({'left':position.left,'top':headerHeight})
                .slideToggle(200);
        })

		// Show admin sub-menu
        .on('click','#menu_admin',function() {
            var position = $(this).position();
            var headerHeight = $('.header').outerHeight();
            $('.addmenu-admin')
                .css({'left':position.left,'top':headerHeight})
                .slideToggle(200);
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         JQuery_UI Calendar
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        .on('mouseenter','.submission',function(e) {
            e.preventDefault();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {get_calendar_param: true},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var selected_date = $('input#selected_date').val();
                    inititdatepicker(result,selected_date);
                }
            });
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         User Profile
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
		 // Process personal info form
        .on('click',".profile_persoinfo_form",function(e) {
            e.preventDefault();
            processform("profile_persoinfo_form","feedback_perso");
        })

		// Process coordinates (email, etc) form
        .on('click',".profile_emailinfo_form",function(e) {
            e.preventDefault();
            processform("profile_emailinfo_form","feedback_mail");
        })

		// Send a verification email to the user if a change of password is requested
        .on('click',".change_pwd",function(){
            var email = $(this).attr("id");
            send_verifmail(email);
            showfeedback('<p id="success">An email with instructions has been sent to your address</p>','feedback_perso');
        })

		// Open a dialog box
        .on('click',"#modal_change_pwd",function(){
            var email = $("input#ch_email").val();

            if (email === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#ch_email").focus();
                return false;
            }

            if (!checkemail(email)) {
                showfeedback('<p id="warning">Invalid email</p>');
                $("input#ch_email").focus();
                return false;
            }
            send_verifmail(email);
        })

		// Password change form (email + new password)
        .on('click',".conf_changepw",function(){
            var username = $("input#ch_username").val();
            var password = $("input#ch_password").val();
            var conf_password = $("input#ch_conf_password").val();

            if (password === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#ch_password").focus();
                return false;
            }

            if (conf_password === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#ch_conf_password").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    conf_changepw: true,
                    username: username,
                    password: password,
                    conf_password: conf_password},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result === "changed") {
                        showfeedback('<p id="success">Your password has been modified.</p>');
                    } else if (result === "mismatch") {
                        showfeedback('<p id="warning">Passwords must match!</p>');
                    }
                }
            });
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Admin tools
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // User management tool: sort users
		.on('change','.user_select',function(e) {
            e.preventDefault();
            var filter = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    user_select: filter
                    },
                success: function(data){
                    var result = jQuery.parseJSON(data);
					$('#user_list')
                        .hide()
                        .html(result)
                        .fadeIn(200);
                }
            });
            return false;
        })

        // User Management tool: Modify user status
        .on('change','.modify_status',function(e) {
            e.preventDefault();
            var username = $(this).attr("data-user");
            var option = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    modify_status: true,
                    username: username,
                    option: option},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result.msg === "deleted") {
                        showfeedback('<p id="success">Account successfully deleted!</p>');
                        $('#section_'+username).remove();
                    } else {
                        showfeedback('<p id="success">'+result.status+'</p>');
                    }

                    setTimeout(function() {
                       $('#user_list')
                        .hide()
                        .html(result.content)
                        .fadeIn(200);
                    },2000);
                }
            });
            return false;
        })

        // Check db integrity (matching session/presentation table)
        .on('click','.db_check',function(e){
            e.preventDefault();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {db_check: true},
                beforeSend: function() {
                    $('#loading').show();
                },
                complete: function () {
                    $('#loading').hide();
                },
                success: function(data){
                    var json = jQuery.parseJSON(data);
                    showfeedback('<p id="success">OK</p>','db_check');
                }
            });
        })

		// Send an email to the mailing list
        .on('click','.mailing_send',function(e) {
            e.preventDefault();
            var spec_head = $("input#spec_head").val();
            var spec_msg = tinyMCE.activeEditor.getContent();

            if (spec_head === "") {
                showfeedback('<p id="warning">You must precise a subject</p>');
                $("input#spec_head").focus();
                return false;
            }

            if (spec_msg === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("textarea#spec_msg").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    mailing_send: true,
                    spec_head: spec_head,
                    spec_msg: spec_msg},
                beforeSend: function() {
                    loadingDiv($(this));
                },
                complete: function() {
                    removeLoading($(this));
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result === "sent") {
                        showfeedback('<p id="success">Your message has been sent!</p>');
                    } else if (result === "not_sent") {
                        showfeedback('<p id="warning">Oops, something went wrong!</p>');
                    }
                }
            });
            return false;
        })

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
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    post_del: true,
                    postid: postid},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result === true) {
                        $('.postcontent')
                            .hide()
                            .html('<p id="success">Post successfully deleted</p>')
                            .fadeIn(200);
                        showpostform(false);
                    } else {
                        showfeedback('<p id="warning">We could not delete this post from the database</p>');
                    }

                }
            });
        })

        // Add a news to the homepage
        .on('click','.post_add,.post_mod',function(e) {
            e.preventDefault();
            var op = $(this).attr('name');
            var postid = $(this).attr('data-id');
            var title = $('input#post_title').val();
            var content = tinyMCE.get('post_content').getContent();
            var username = $("input#post_username").val();
            var homepage = $("select#post_homepage").val();

            if (title === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $('input#title').focus();
                return false;
            }

            if (content === "") {
                showfeedback('<p id="warning">This field is required</p>');
                tinymce.execCommand('mceFocus',false,'consent');
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    post_add: op,
                    postid: postid,
                    username: username,
                    title: title,
                    homepage: homepage,
                    content: content},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result === true) {
                        $('.postcontent')
                            .hide()
                            .html('<p id="success">Thank you for your post!</p>')
                            .fadeIn(200);
                        showpostform(false);
                    } else {
                        showfeedback("<p id='warning'>Sorry, something has gone wrong!</p>");
                    }
                }
            });
            return false;
        })

        // Configuration of the application
        .on('click','.config_form_site',function(e) {
            e.preventDefault();
            processform("config_form_site","feedback_site");
        })

        .on('click','.config_form_lab',function(e) {
            e.preventDefault();
            processform("config_form_lab","feedback_lab");
        })

        .on('click','.config_form_jc',function(e) {
            e.preventDefault();
            processform("config_form_jc","feedback_jc");
        })

        .on('click','.config_form_mail',function(e) {
            e.preventDefault();
            processform("config_form_mail","feedback_mail");
        })

        .on('click','.config_form_session',function(e) {
            e.preventDefault();
            processform("config_form_session","feedback_jcsession");
        })

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

        // Number of sessions to show
        .on('change','.show_sessions',function(e) {
            var nbsession = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    show_session: nbsession},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    $('#sessionlist')
                        .hide()
                        .html(result)
                        .fadeIn(200);
                }
            });
        })

        // Modify chairman
        .on('change','.mod_chair',function(e) {
            var session = $(this).attr('data-session');
            var chair = $(this).val();
            var chairID = $(this).attr('data-chair');
            var presid = $(this).attr('data-pres');

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    mod_chair: true,
                    session: session,
                    chair: chair,
                    chairID: chairID,
                    presid: presid},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var feedbackdiv = 'feedback_'+session;
                    if (result !== false) {
                        showfeedback("<span id='success'>Modifications have been made</span>",feedbackdiv);
                    } else {
                        showfeedback("<span id='warning'>Oops something has gone wrong</span>",feedbackdiv);
                    }
                }
            });
        })

        // Modify session time
        .on('change','.set_sessiontime',function(e) {
            var session = $(this).attr('data-session');
            var timefrom = $('select#timefrom_'+session).val();
            var timeto = $('select#timeto_'+session).val();
            var time = timefrom+","+timeto;

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    mod_session_time: true,
                    session: session,
                    time: time},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var feedbackdiv = 'feedback_'+session;
                    if (result !== false) {
                        showfeedback("<span id='success'>Modifications have been made</span>",feedbackdiv);
                    } else {
                        showfeedback("<span id='warning'>Oops something has gone wrong</span>",feedbackdiv);
                    }
                }
            });
        })

        // Modify session time
        .on('change','.set_sessiontype',function(e) {
            var type = $(this).val();
            var session = $(this).attr('id');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    mod_session_type: true,
                    session: session,
                    type: type},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var feedbackdiv = 'feedback_'+session;
                    if (result !== false) {
                        showfeedback("<span id='success'>Modifications have been made</span>",feedbackdiv);
                    } else {
                        showfeedback("<span id='warning'>Oops something has gone wrong</span>",feedbackdiv);
                    }
                }
            });
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Publication lists (Archives/user publications
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Sort publications by years
		.on('change','.archive_select',function(e) {
            e.preventDefault();
            var year = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    select_year: year
                    },
                beforeSend: function() {
                    $('#loading').show();
                },
                complete: function() {
                    $('#loading').hide();
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
					$('#archives_list').html(result);
                }
            });
            return false;
        })

        .on('mouseover','.show_pres',function() {
            $(this)
                .css('background-color','#CF5252')
                .children('a').css('color','#eeeeee');
        })

        .on('mouseleave','.show_pres',function() {
            $(this)
                .css('background-color','#dddddd')
                .children('a').css('color','#CF5252');
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Presentation submission
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Select a wish
        .on('change','#select_wish',function(e) {
            var presid = $(this).val();
            var form = $('.wishform');
            showpubform(form,presid,'submit');
         })

        // Show download list
        .on('click','.dl_btn',function() {
            $(".dlmenu").toggle();
        })

         // Show uploaded file
         .on('click','.upl_name',function() {
            var uplname = $(this).attr('id');
            var url = "uploads/"+uplname;
            window.open(url,'_blank');
         })

          // Delete uploaded file
         .on('click','.del_upl',function() {
            var uplfilename = $(this).attr('id');
            var uplname = $(this).attr('data-upl');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    del_upl: true,
                    uplname: uplfilename},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result === true) {
                        console.log('Delete upload: '+uplname);
                        $('.upl_info#upl_'+uplname).remove();
                        $('.upl_link#upl_'+uplname).remove();
                    }
                }
            });
         })

         // Select submission type
         .on('change','select#type',function(e) {
            e.preventDefault();
            var type = $(this).val();
            if (type == "guest") {
                $('.submission #guest').fadeIn();
            } else {
                $('.submission #guest').hide();
            }
         })

        // Submit a presentation
        .on('click','.submit_pres',function(e) {
            e.preventDefault();
            var operation = $(this).attr('name');
            var form = $(this).closest('#submission_form');
            var type = $("select#type").val();
            var title = $("input#title").val();
            var authors = $("input#authors").val();
            var summary = $("textarea#summary").val();

            if (operation !== "suggest") {
                var date = $("input#datepicker").val();
                if ((date === "0000-00-00" || date === "") && type !== "wishlist") {
                    showfeedback('<p id="warning">You must choose a date!</p>');
                    $("input#datepicker").focus();
                    return false;
                }
            }

            if ($('input.upl_link')[0]) {
                var links = new Array();
                $('input.upl_link').each(function(){
                    var link = $(this).val();
                    links.push(link);
                });
                links = links.join(',');
                $('#submit_form').append("<input type='hidden' name='link' value='"+links+"'>");
            }

            if (title === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#title").focus();
                return false;
            }

            if (authors === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#authors").focus();
                return false;
            }

            if (type === "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("select#type").focus();
                return false;
            }

            if (type === "guest") {
                var orator = $("input#orator").val();
                if (orator === "") {
                    showfeedback('<p id="warning">This field is required</p>');
                    $("input#orator").focus();
                    return false;
                }
            }

            if (summary === "") {
                showfeedback('<p id="warning">Please provide a short description of your presentation (e.g. abstract)</p>');
                $("textarea#summary").focus();
                return false;
            }

            var data = $("#submit_form").serialize();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: data,
                beforeSend: function() {
                    loadingDiv('.submission');
                },
                complete: function() {
                    removeLoading('.submission');
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result.status == true) {
                        validsubmitform(form,result.msg);
                        setTimeout(function() {
                            showpubform(form,false);
                        },2000);
                    } else {
                        showfeedback(result.msg);
                    }
                }
            });
            return false;
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Contact form
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
       // Send an email to the chosen organizer
	   .on('click','.contact_send',function(e) {
            e.preventDefault();
            var admin_mail = $("select#admin_mail").val();
            var message = $("textarea#message").val();
            var contact_mail = $("input#contact_mail").val();
            var contact_name = $("input#contact_name").val();

            if (admin_mail == "none") {
                showfeedback('<p id="warning">You must select an organizer</p>');
                $("select#admin_mail").focus();
                return false;
            }

            if (contact_mail == "Your email") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#contact_mail").focus();
                return false;
            }

            if (!checkemail(contact_mail)) {
                showfeedback('<p id="warning">Invalid email!</p>');
                $("input#contact_mail").focus();
                return false;
            }

            if (contact_name == "Your name") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#contact_name").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    contact_send: true,
                    admin_mail: admin_mail,
                    message: message,
                    name: contact_name,
                    mail: contact_mail},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result === "sent") {
                        showfeedback('<p id="success">Your message has been sent!</p>');
                    } else if (result === "not_sent") {
                        showfeedback('<p id="warning">Oops, something went wrong!</p>');
                    }
                }
            });
            return false;
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

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
          Publication modal
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
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
            var id_pres = $(this).attr('data-id');
            var date = $(this).attr('data-date');
            showmodal('submission_form');
            showpubform(modalpubform,id_pres,'submit',date);
        });

	// Process events happening on the publication modal dialog box
    $('.modalContainer')
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
            $(".header_title").text('Delete confirmation');
        })

        // Going back to publication
        .on('click',".pub_back_btn",function(){
            showmodal('submission_form');
            $(".header_title").text('Presentation');
        })

        // Confirm delete publication
        .on('click',"#confirm_pubdel",function(e) {
            e.preventDefault();
            var id_pres = $("input#del_pub").val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {del_pub: id_pres},
                beforeSend: function() {
                    $('#loading').show();
                },
                complete: function() {
                    $('#loading').hide();
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    showfeedback('<p id="success">Publication deleted</p>');
                    close_modal('.modalContainer');
                    $('#'+id_pres).remove();
                }
            });
        })

        // Dialog change password
        .on('click',".modal_trigger_changepw",function(e){
            e.preventDefault();
            showmodal('user_changepw');
            $(".header_title").text('Change password');
        })

        // Going back to Login Forms
        .on('click',".back_btn",function(){
            showmodal('user_login');
            $(".header_title").text('Sign in');
            return false;
        })

        // Go to sign up form
        .on('click','.gotoregister',function(e) {
            e.preventDefault();
            showmodal('user_register');
            $(".header_title").text('Sign Up');
        })

        // Delete user account confirmation form
        .on('click',"#confirmdeleteuser",function(e) {
            e.preventDefault();
            var username = $("input#del_username").val();
            var password = $("input#del_password").val();

            var valid = checkform('login_form');
            if (valid === false) { return false; }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {username: username,
                    password: password,
                    delete_user: true},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result == 'deleted') {
                        window.location.href = "index.php?page=logout";
                        location.reload();
                    } else if (result == "wrong_username") {
                        showfeedback('<p id="warning">Wrong username</p>');
                    } else if (result == "wrong_password") {
                        showfeedback('<p id="warning">Wrong username/password</p>');
                    }
                }
            });
            return false;
        })

        // Login form
        .on('click',".login",function(e) {
            e.preventDefault();
            var username = $("input#log_username").val();
            var password = $("input#log_password").val();

            var valid = checkform('login_form');
            if (valid === false) { return false; }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {username: username,
                    password: password,
                    login: true
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result.status == true) {
                        $('#login_form')
                            .hide()
                            .html('<p id="success">Welcome back!</p>')
                            .fadeIn(200);
                        setTimeout(function() {
                            location.reload();
                        },2000);
                    } else if (result.msg == "wrong_username") {
                        showfeedback('<p id="warning">Wrong username</p>');
                    } else if (result.msg == "wrong_password") {
                        showfeedback('<p id="warning">Wrong password. ' + result.status + ' login attempts remaining</p>');
                    } else if (result.msg == "blocked_account") {
                        showfeedback('<p id="warning">Wrong password. You have exceeded the maximum number ' +
                        'of possible attempts, hence your account has been deactivated for security reasons. ' +
                        'We have sent an email to your address including an activation link.</p>');
                    } else if (result.msg == "not_activated") {
                        showfeedback('<p id="warning">Sorry, your account is not activated yet. ' +
                            '<br> You will receive an email as soon as your registration is confirmed by an admin.<br> ' +
                            'Please <a href="index.php?page=contact">contact us</a> if you have any question.</p>');
                    }
                }
            });
            return false;
        })

        // Sign Up Form
        .on('click',".register",function(e) {
            e.preventDefault();
            var firstname = $("input#firstname").val();
            var lastname = $("input#lastname").val();
            var username = $("input#username").val();
            var password = $("input#password").val();
            var conf_password = $("input#conf_password").val();
            var email = $("input#email").val();
            var position = $("select#position").val();

            var valid = checkform('register_form');
            if (valid === false) { return false; }

            if (password != conf_password) {
                showfeedback('<p id="warning">Passwords must match</p>');
                $("input#conf_password").focus();
                return false;
            }

            if (!checkemail(email)) {
                showfeedback('<p id="warning">Invalid email!</p>');
                $("input#email").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {
                    firstname: firstname,
                    lastname: lastname,
                    username: username,
                    password: password,
                    conf_password: conf_password,
                    email: email,
                    position: position,
                    register: true
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    if (result == true) {
                        $('#user_register')
                            .hide()
                            .html('<p id="success">Your account has been created. You will receive an email after its validation by our admins.</p>')
                            .fadeIn(200);
                        setTimeout(function() {
                            close_modal(".modalContainer");
                        }, 5000);
                    } else if (result === "exist") {
                        showfeedback('<p id="warning">This username/email address already exist in our database</p>');
                    } else if (result === "mail_pb") {
                        showfeedback('<p id="warning">Sorry, we have not been able to send a verification email to the organizers. Your registration cannot be validated for the moment. Please try again later.</p>');
                    }

                }
            });
            return false;
        });
});
