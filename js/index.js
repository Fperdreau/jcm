/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
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
// Spin animation when a page is loading
var $loading = $('#loading').hide();
var step = 1;

// Process submitted form
var processform = function(formid,feedbackid) {
    if (typeof feedbackid == "undefined") {
        feedbackid = ".feedback";
    }
    var data = $("#" + formid).serialize();
    console.log(data);
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: false,
        data: data,
        success: function(data){
            var result = jQuery.parseJSON(data);
            console.log("returned result:"+result);
            showfeedback(result,feedbackid);
        }
    });
};

// Check email validity
function checkemail(email) {
    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    return pattern.test(email);
};

//Show feedback
var showfeedback = function(message,selector) {
    if (typeof selector == "undefined") {
        selector = ".feedback";
    }
    $(""+selector)
        .show()
        .html(message)
        .fadeOut(5000);
};

// send verification email after signing up
var send_verifmail = function(email) {
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        async: false,
        data: {
            change_pw: true,
            email: email},
        success: function(data){
            var result = jQuery.parseJSON(data);
            console.log(result);
            if (result === "sent") {
                showfeedback('<p id="success">A verification email has been sent to your address</p>');
            } else if (result === "wrong_email") {
                showfeedback('<p id="warning">Wrong email address</p>');
            }
        }
    });
};

// initialize jQuery-UI Calendar
var inititdatepicker = function(jc_day,selected_date,the_selected_dates) {
    $('#datepicker').datepicker({
        defaultDate: selected_date,
        firstDay: 1,
        dateFormat: 'yy-mm-dd',
        inline: true,
        showOtherMonths: true,
        beforeShowDay: function(date) {
            var day = date.getDay();
            var days = new Array("sunday","monday","tuesday","wednesday","thursday","friday","saturday");
            if( ($.inArray($.datepicker.formatDate('dd-mm-yy',date), the_selected_dates) > -1) && (days[day] == jc_day)) {
                return [false,"bookedday","Booked out"];
            } else if( !($.inArray($.datepicker.formatDate('dd-mm-yy',date), the_selected_dates) > -1) && (days[day] == jc_day)) {
                return [true,"jcday","available"];
            } else {
                return [false,"","Not a journal club day"];
            }
        }
    });
};

// Spin animation when a page is loading
var loadspin =  function (action) {
    if (typeof action == undefined) {
        action = "start";
    }
    var opts = {
        lines: 13, // The number of lines to draw
        length: 20, // The length of each line
        width: 10, // The line thickness
        radius: 30, // The radius of the inner circle
        corners: 1, // Corner roundness (0..1)
        rotate: 0, // The rotation offset
        direction: 1, // 1: clockwise, -1: counterclockwise
        color: '#000', // #rgb or #rrggbb or array of colors
        speed: 1, // Rounds per second
        trail: 60, // Afterglow percentage
        shadow: false, // Whether to render a shadow
        hwaccel: false, // Whether to use hardware acceleration
        className: 'spinner', // The CSS class to assign to the spinner
        zIndex: 2e9, // The z-index (defaults to 2000000000)
        top: '50%', // Top position relative to parent
        left: '50%' // Left position relative to parent
    };
    var target = document.getElementById('loading');
    var spinner = new Spinner(opts).spin(target);

    if (action == "start") {
    } else {
        spinner.stop(target);
    }

};

// Set up tinyMCE (rich-text textarea)
var tinymcesetup = function() {
    tinymce.init({
        mode: "textareas",
        selector: ".tinymce",
        width: "90%",
        height: 300,
        plugins: [
            "advlist autolink lists charmap print preview hr spellchecker",
            "searchreplace wordcount visualblocks visualchars code fullscreen",
            "save contextmenu directionality template paste textcolor"
        ],
        content_css: "js/tinymce/skins/lightgray/content.min.css",
        toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons",
        style_formats: [
            {title: 'Bold text', inline: 'b'},
            {title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
            {title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
            {title: 'Example 1', inline: 'span', classes: 'example1'},
            {title: 'Example 2', inline: 'span', classes: 'example2'},
            {title: 'Table styles'},
            {title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
        ]
    });

};

// Load page by clicking on menu sections
var loadpageonclick = function(pagetoload,param) {
    param = typeof param !== 'undefined' ? param : false;
    var stateObj = { page: pagetoload };

    if (param == false) {
        jQuery.ajax({
            url: 'pages/'+pagetoload+'.php',
            type: 'GET',
            async: true,
            data: param,
            success: function(data){
                var json = jQuery.parseJSON(data);
                history.pushState(stateObj, pagetoload, "index.php?page="+pagetoload);

                $('#loading').hide();
                $('#pagecontent')
                    .html('<div>'+json+'</div>')
                    .fadeIn('slow');
                tinymcesetup();

            }
        });
    } else {
        jQuery.ajax({
            url: 'pages/'+pagetoload+'.php',
            type: 'GET',
            async: false,
            data: param,
            success: function(data){
                var json = jQuery.parseJSON(data);
                history.pushState(stateObj, pagetoload, "index.php?page="+pagetoload+"&"+param);

                $('#loading').hide();
                $('#pagecontent')
                    .html('<div>'+json+'</div>')
                    .fadeIn('slow');
                tinymcesetup();

            }
        });
    }

};

// Parse URL
var parseurl = function() {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    vars = vars.slice(1,vars.length);
    vars = vars.join("&");
    console.log(vars);
    return vars;
};

// Get url params ($_GET)
var getParams = function() {
    var url = window.location.href;
    var splitted = url.split("?");
    if(splitted.length === 1) {
        return {};
    }
    var paramList = decodeURIComponent(splitted[1]).split("&");
    var params = {};
    for(var i = 0; i < paramList.length; i++) {
        var paramTuple = paramList[i].split("=");
        params[paramTuple[0]] = paramTuple[1];
    }
    return params;
};

$( document ).ready(function() {

    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
     Main body
     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    $('.mainbody')

        .ready(function() {

			// Automatically parse url and load the corresponding page
            var params = getParams();
            var page = params.page;

            if (page == undefined) {
                loadpageonclick('home',false);
            } else {
                if (page != false && page == 'install') {
                    if (params.step != undefined) {
                        loadpageonclick('install','step='+params.step);
                    } else {
                        loadpageonclick('install','step=1');
                    }
                } else if (page != false && page != 'install') {
                    var urlparam = parseurl();
                    loadpageonclick(page,''+urlparam);
                }
            }
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Header menu/Sub-menu
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

        // Display/Hide sub-menu
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
                    $('.addmenu-admin').hide();
                }
            }
            if(!$(event.target).closest('#menu_pres').length) {
                if($('.addmenu-pres').is(":visible")) {
                    $('.addmenu-pres').hide();
                }
            }
        })

		// Show presentation sub-menu
        .on('click','#menu_pres',function() {
            $('.addmenu-pres').slideToggle(200);
        })

		// Show admin sub-menu
        .on('click','#menu_admin',function() {
            $('.addmenu-admin').slideToggle(200);
        })

		// Log out
        .on('click',"#logout",function(){
            jQuery.ajax({
                url: 'pages/logout.php',
                type: 'POST',
                async: false,
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);
                    window.location = "index.php";
                }
            });
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         JQuery_UI Calendar
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        .on('mouseover','input#datepicker',function(e) {
            e.preventDefault();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {get_calendar_param: true},
                success: function(data){
                    var result = jQuery.parseJSON(data);

                    var jc_day = result.jc_day;
                    var booked_dates = result.booked_dates;
                    var selected_date = $('input#selected_date').val();
                    inititdatepicker(jc_day,selected_date,booked_dates);
                }
            });
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         User Profile
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

		 // Process personal info form
        .on('click',".profile_persoinfo_form",function(e) {
            e.preventDefault();
            processform("profile_persoinfo_form",".feedback_perso");
        })

		// Process coordinates (email, etc) form
        .on('click',".profile_emailinfo_form",function(e) {
            e.preventDefault();
            processform("profile_emailinfo_form",".feedback_mail");
        })

		// Send a verification email to the user if a change of password is requested
        .on('click',".change_pwd",function(){
            var email = $(this).attr("id");
            send_verifmail(email);
            showfeedback('<p id="success">An email with instructions has been sent to your address</p>','.feedback_perso');
        })

		// Open a dialog box
        .on('click',"#modal_change_pwd",function(){
            var email = $("input#ch_email").val();

            if (email == "") {
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

            if (password == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#ch_password").focus();
                return false;
            }

            if (conf_password == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#ch_conf_password").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {
                    conf_changepw: true,
                    username: username,
                    password: password,
                    conf_password: conf_password},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);

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
                async: false,
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

		// Do a full backup (database + files) if asked
        .on('click','.fullbackup',function(){
            var webproc = true;

            jQuery.ajax({
                url: 'cronjobs/full_backup.php',
                type: 'GET',
                async: false,
                data: {webproc: webproc},
                success: function(data){
                    console.log(data);

                    var json = jQuery.parseJSON(data);
                    console.log(json);
                    $('#full_backup').append('<div class="file_link" data-url="'+json+'" style="width: auto;"><a href="' + json + '">Download backup file</a></div>');
                }
            });
        })

		// Backup the database only if asked
        .on('click','.dbbackup',function(){
            var webproc = true;
            jQuery.ajax({
                url: 'cronjobs/db_backup.php',
                type: 'GET',
                async: false,
                data: {webproc: webproc},
                success: function(data){
                    console.log(data);

                    var json = jQuery.parseJSON(data);
                    console.log(json);
                    $('#db_backup').append('<div class="file_link" data-url="'+json+'" style="width: auto;"><a href="' + json + '">Download backup file</a></div>');
                }
            });
        })

        // Export mailing list to xls
        .on('click','.exportdb',function(){
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {
                    export: true,
                    tablename: "mailinglist"},
                success: function(data){
                    var json = jQuery.parseJSON(data);
                    console.log(json);
                    $('#exportdb').append('<div class="file_link" data-url="'+json+'" style="width: auto;"><a href="' + json + '">Download XLS file</a></div>');
                }
            });
        })

		// Show link to created backup
        .on('click','.file_link', function(){
            var link = $(this).attr('data-url');
            $(this)
                .html('<p id="success">Downloaded</p>')
                .fadeOut(5000);
        })

		// Send an email to the mailing list
        .on('click','.mailing_send',function(e) {
            e.preventDefault();
            var spec_head = $("input#spec_head").val();
            var spec_msg = tinyMCE.activeEditor.getContent();

            if (spec_head == "") {
                showfeedback('<p id="warning">You must precise a subject</p>');
                $("input#spec_head").focus();
                return false;
            }

            if (spec_msg == "") {
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
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);

                    if (result === "sent") {
                        showfeedback('<p id="success">Your message has been sent!</p>');
                    } else if (result === "not_sent") {
                        showfeedback('<p id="warning">Oops, something went wrong!</p>');
                    }
                }
            });
            return false;
        })

		// User Management tool: Modify user status
        .on('change','.modify_status',function(e) {
            e.preventDefault();
            var username = $(this).attr("data-user");
            var option = $(this).val();
            console.log(username);
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
                    if (result === "deleted") {
                        showfeedback('<p id="success">Account successfully deleted!</p>');
                        $('#section_'+username).remove();
                    } else {
                        showfeedback('<p id="success">'+result+'</p>');
                    }
                }
            });
            return false;
        })

		// Add a news to the homepage
        .on('click','.post_send',function(e) {
            e.preventDefault();
            var new_post = tinyMCE.activeEditor.getContent();
            var fullname = $("input#fullname").val();
            console.log(new_post);
            if (new_post == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("textarea#post").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {
                    post_send: true,
                    fullname: fullname,
                    new_post: new_post},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);

                    if (result === "posted") {
                        showfeedback("<p id='success'>Your message has been posted on the homepage!</p>");
                    }
                }
            });
            return false;
        })

		// Configuration of the application
        .on('click','.config_form_site',function(e) {
            e.preventDefault();
            processform("config_form_site",".feedback_site");
        })

        .on('click','.config_form_lab',function(e) {
            e.preventDefault();
            processform("config_form_lab",".feedback_lab");
        })

        .on('click','.config_form_jc',function(e) {
            e.preventDefault();
            processform("config_form_jc",".feedback_jc");
        })

        .on('click','.config_form_mail',function(e) {
            e.preventDefault();
            processform("config_form_mail",".feedback_mail");
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
                async: false,
                data: {
                    select_year: year
                    },
                success: function(data){
                    var result = jQuery.parseJSON(data);
					$('#archives_list').html(result);
                }
            });
            return false;
        })

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
         Presentation submission
         %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Submit a presentation
        .on('click','.submit',function(e) {
            e.preventDefault();
            var date = $("input#datepicker").val();
            var title = $("input#title").val();
            var type = $("select#type").val();
            var authors = $("input#authors").val();
            var orator = $("input#orator").val();
            var link = $("input#link").val();
            console.log('uploaded file:'+link);
            console.log('orator: '+orator);

            if (type == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("select#type").focus();
                return false;
            }

            if (orator == "" && type == "guest") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#orator").focus();
                return false;
            }

            if (date == "" && type !== "wishlist") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#datepicker").focus();
                return false;
            }

            if (title == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#title").focus();
                return false;
            }

            if (authors == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#authors").focus();
                return false;
            }

            processform("submit_form");
            return false;
        })

        // Update a presentation
        .on('click','.update',function(e) {
            e.preventDefault();
            var date = $("input#datepicker").val();
            var title = $("input#title").val();
            var type = $("select#type").val();
            var authors = $("input#authors").val();
            var orator = $("input#orator").val();

            if (type == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("select#type").focus();
                return false;
            }

            if (orator == "" && type == "guest") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#orator").focus();
                return false;
            }

            if (date == "" && type !== "wishlist") {
                showfeedback('<p id="warning">You must choose a date</p>');
                $("input#datepicker").focus();
                return false;
            }

            if (title == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#title").focus();
                return false;
            }

            if (authors == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#authors").focus();
                return false;
            }

            processform("submit_form");
            return false;
        })

        // Suggest a wish
        .on('click','.suggest',function(e) {
            e.preventDefault();
            var title = $("input#title").val();
            var type = $("select#type").val();
            var authors = $("input#authors").val();
            var orator = $("input#orator").val();

            if (type == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("select#type").focus();
                return false;
            }

            if (orator == "" && type == "guest") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#orator").focus();
                return false;
            }

            if (title == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#title").focus();
                return false;
            }

            if (authors == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#authors").focus();
                return false;
            }

            processform("submit_form");
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
                    console.log(result);

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
          Login dialog
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Upload a file
		.on('mouseover','#upload',function() {
            $(this).fileupload({
                dataType: 'json',
                done: function (e, data) {
                    $.each(data.result.files, function (index, file) {
                        $('<p/>').text(file.name).appendTo(document.body);
                    });
                }
            })
        })

		// Trigger modal dialog box for log in/sign up
        .on('mouseover',"a[rel*=leanModal]",function(e) {
            e.preventDefault();
            $(this).leanModal({top : 50, overlay : 0.6, closeButton: ".modal_close" });
        })

		// Trigger modal dialog box for publications (show/modify/delete forms)
        .on('mouseover',"a[rel*=pub_leanModal]",function(e) {
            e.preventDefault();
            $(this).leanModal({top : 50, width : 500, overlay : 0.6, closeButton: ".modal_close" });
        })

		// Show publication information on click
        .on('click','#modal_trigger_pubcontainer',function(e){
            e.preventDefault();
            var id_pres = $(this).attr('data-id');
            console.log(id_pres);
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {
                    show_pub: id_pres},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);
                    $(".publication_form")
                        .show()
                        .html(result);
                    $(".pub_delete").hide();
                    $(".pub_modify").hide();
                    $(".header_title").text('Presentation');
                }
            });
        })

        // Dialog log in
        .on('click',"#modal_trigger_login",function(e){
            e.preventDefault();
            $(".user_login").show();
            $(".user_register").hide();
            $(".user_changepw").hide();
            $(".user_delete").hide();
            $(".pub_delete").hide();
            $(".header_title").text('Log in');
        })

        // Dialog sign up
        .on('click',"#modal_trigger_register",function(e){
            e.preventDefault();
            $(".user_register").show();
            $(".user_login").hide();
            $(".user_delete").hide();
            $(".user_changepw").hide();
            $(".pub_delete").hide();
            $(".header_title").text('Sign up');
        })

        // Delete user account dialog box
        .on('click',"#modal_trigger_delete",function(e){
            e.preventDefault();
            $(".user_register").hide();
            $(".user_login").hide();
            $(".user_delete").show();
            $(".user_changepw").hide();
            $(".pub_delete").hide();
            $(".header_title").text('Delete confirmation');
        })

        // Replace file
        .on('click','.file_replace',function(e) {
            e.preventDefault();
            var id_pres = $(this).attr("data-id");
            console.log(id_pres);
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {del_file: id_pres},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);
                    $(".upload_form").html("<form method='post' action='js/mini-upload-form/upload.php' enctype='multipart/form-data' id='upload'>" +
                        "<div class='upl_note'></div><div id='drop'><a>Add a file</a><input type='file' name='upl' id='upl' multiple/> Or drag it here</div><ul></ul></form>");
                }
            });
        });

	// Process events happening on the publication modal dialog box
    $('.pub_popupContainer')
		// Show publication modification form
        .on('click','.modify_ref',function(e) {
            e.preventDefault();
            var id_pres = $(this).attr("data-id");
            console.log(id_pres);
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {mod_pub: id_pres},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);
                    $(".pub_delete").hide();
					$(".publication_form").hide();
                    $('.pub_modify')
                    	.html(result)
                    	.show();
                }
            });
        })

		// Show publication deletion confirmation
        .on('click',".delete_ref",function(e){
            e.preventDefault();
            var id_pres = $(this).attr("data-id");
            console.log(id_pres);
            $(".pub_delete")
                .show()
                .append('<input type=hidden id="del_pub" value="' + id_pres + '"/>');
            $(".publication_form").hide();
            $(".header_title").text('Delete confirmation');
        })

        // Going back to publication
        .on('click',".pub_back_btn",function(){
            $(".publication_form").show();
            $(".pub_delete").hide();
            $(".pub_modify").hide();
            $(".header_title").text('Presentation');
            return false;
        })

        // Confirm delete publication
        .on('click',"#confirm_pubdel",function(e) {
            e.preventDefault();
            var id_pres = $("input#del_pub").val();
            console.log("pub to del: "+id_pres);
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: false,
                data: {del_pub: id_pres},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);
                    showfeedback('<p id="success">Publication deleted</p>');
                    $('#'+id_pres).remove();
                }
            });
        });

	// Process events happening on the login/sign up modal dialog box
    $(".popupContainer")
        // Dialog change password
        .on('click',".modal_trigger_changepw",function(e){
            e.preventDefault();
            $(".user_changepw").show();
            $(".user_login").hide();
            $(".user_delete").hide();
            $(".user_register").hide();
            $(".pub_delete").hide();
            $(".header_title").text('Change password');
        })

        // Going back to Login Forms
        .on('click',".back_btn",function(){
            $(".user_login").show();
            $(".user_delete").hide();
            $(".user_register").hide();
            $(".user_changepw").hide();
            $(".pub_delete").hide();
            $(".header_title").text('Login');
            return false;
        })

        // Delete user account confirmation form
        .on('click',"#confirmdeleteuser",function(e) {
            e.preventDefault();
            var username = $("input#del_username").val();
            var password = $("input#del_password").val();

            if (username == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#del_username").focus();
                return false;
            }

            if (password == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#del_password").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
                data: {username: username,
                    password: password,
                    delete_user: true},
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    console.log(result);
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
        .on('click',".login",function() {
            var username = $("input#log_username").val();
            var password = $("input#log_password").val();

            if (username == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#log_username").focus();
                return false;
            }

            if (password == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#log_password").focus();
                return false;
            }

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
                    console.log(result);
                    if (result == 'logok') {
                        location.reload();
                    } else if (result == "wrong_username") {
                        showfeedback('<p id="warning">Wrong username</p>');
                    } else if (result == "wrong_password") {
                        showfeedback('<p id="warning">Wrong username/password</p>');
                    } else if (result == "not_activated") {
                        showfeedback('<p id="warning">Sorry, your account is not activated yet. ' +
                            '<br> You will receive an email as soon as your registration is confirmed by an admin.<br> ' +
                            'Please <a href="index.php?page=contact">contact us</a> if you have any question.</p>');
                    }
                }
            });
            return false;
        })

        // Sign Up Form
        .on('click',"#register",function() {
            var firstname = $("input#firstname").val();
            var lastname = $("input#lastname").val();
            var username = $("input#username").val();
            var password = $("input#password").val();
            var conf_password = $("input#conf_password").val();
            var email = $("input#email").val();
            var position = $("select#position").val();

            if (firstname == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#firstname").focus();
                return false;
            }
            if (lastname == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#lastname").focus();
                return false;
            }
            if (username == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#username").focus();
                return false;
            }

            if (password == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#password").focus();
                return false;
            }

            if (conf_password == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#conf_password").focus();
                return false;
            }

            if (password != conf_password) {
                showfeedback('<p id="warning">Passwords must match</p>');
                $("input#conf_password").focus();
                return false;
            }

            if (email == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#email").focus();
                return false;
            }

            if (!checkemail(email)) {
                showfeedback('<p id="warning">Invalid email!</p>');
                $("input#email").focus();
                return false;
            }

            if (position == "") {
                showfeedback('<p id="warning">This field is required</p>');
                $("input#position").focus();
                return false;
            }

            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                async: true,
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
                    if (result == "created") {
                        $('.user_register').html('<p id="success">Your account has been created. You will receive an email after its validation by our admins.</p>');
                    } else if (result === "mismatch") {
                        showfeedback('<p id="warning">Passwords must match</p>');
                    } else if (result === "wrong_email") {
                        showfeedback('<p id="warning">Invalid email address</p>');
                    } else if (result === "exist") {
                        showfeedback('<p id="warning">This username/email address already exist in our database</p>');
                    }

                }
            });
            return false;
        });

}).ajaxStart(function(){
    $loading.show();
}).ajaxStop(function() {
    $loading.hide();
});

