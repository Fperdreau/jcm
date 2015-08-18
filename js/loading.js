/**
 * Created by U648170 on 10-8-2015.
 */

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
            {title: 'AppTable styles'},
            {title: 'AppTable row 1', selector: 'tr', classes: 'tablerow1'}
        ]
    });
};

// Get page content
function getPage(page, urlparam) {
    if (page == undefined) {
        var params = getParams();
        page = (params.page == undefined) ? 'home':params.page;
    }

    urlparam = (urlparam == undefined) ? parseurl():urlparam;
    urlparam = (urlparam === false || urlparam === "") ? false: urlparam;

    jQuery.ajax({
        url: 'php/form.php',
        data: {get_app_status: true},
        type: 'POST',
        async: true,
        success: function(data) {
            var json = jQuery.parseJSON(data);
            if (json === 'Off' && page != 'admin') {
                $('#pagecontent')
                    .html("<div id='content'><div style='vertical-align: middle; margin-top: 20%; text-align: center;'>" +
                    "<div style='font-size: 1.6em; font-weight: 600; margin-bottom: 20px;'>Sorry</div><div> the website is currently under maintenance.</div></div></div>")
                    .fadeIn(200);
            } else {
                loadPageContent(page,urlparam);
            }
        }
    })
}

/**
 * Retrieve and display page content
 * @param page
 * @param urlparam
 */
function loadPageContent(page,urlparam) {
    jQuery.ajax({
        url: 'php/form.php',
        data: {getPage: page},
        type: 'POST',
        async: true,
        success: function(data) {
            var json = jQuery.parseJSON(data);
            if (json.status === false) {
                $('#pagecontent').html(json.msg);
            } else {
                displayPage(page,json.pageName,urlparam);
            }
        }
    })
}

// Load page by clicking on menu sections
var displayPage = function(page,pagetoload,param) {
    var stateObj = { page: pagetoload };
    var url = (param === false) ? "index.php?page="+page:"index.php?page="+page+"&"+param;

    jQuery.ajax({
        url: 'pages/'+pagetoload+'.php',
        type: 'GET',
        async: true,
        data: param,
        beforeSend: function() {
            loadingDiv('#pagecontent')
        },
        complete: function () {
            removeLoading('#pagecontent')
        },
        success: function(data){
            var json = jQuery.parseJSON(data);
            history.pushState(stateObj, pagetoload, url);

            $('#pagecontent')
                .hide()
                .empty()
                .html(json);

            $('#content').children("section").each(function() {
                $(this).fadeIn(200);
            });
            tinymcesetup();
            var callback = showPlugins;
            getPlugins(pagetoload, callback);
        }
    });
};

// Parse URL
var parseurl = function() {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    vars = vars.slice(1,vars.length);
    vars = vars.join("&");
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

// Show loading animation
function loadingDiv(divId) {
    $(""+divId)
        .fadeOut(200)
        .append("<div class='loadingDiv' style='width: 100%; height: 100%;'></div>")
        .show();
}

// Remove loading animation
function removeLoading(divId) {
    var el = $(""+divId);
    el.children('.loadingDiv')
        .fadeOut('slow')
        .remove();
    el.fadeIn(200);
}

// Responsive design part
function adapt() {
    $('#float_menu').hide();
    var headerwidth = $("#sitetitle").outerWidth() + $(".topnav").outerWidth() + $("#login_box").outerWidth() + 10;

    if ($(window).width() <= headerwidth) {
        $("#float_menu").show();
        $(".topnav")
            .hide();
    } else {
        $("#float_menu").hide();
        $('.topnav')
            .css('display','inline-block')
            .show();
    }

    var height = $(window).height();
    $('#core').css('min-height',height+"px");
}

$( document ).ready(function() {

    $(window).resize(function () {
        adapt();
    });

    $('body').ready(function() {
        // Automatically parse url and load the corresponding page
        getPage();
        adapt();
    })

});