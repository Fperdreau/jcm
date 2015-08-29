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
 * Set up tinyMCE (rich-text textarea)
 */
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

/**
 * Parse url and get page content accordingly
 * @param page
 * @param urlparam
 */
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
            if (json.pageName === null) {
                $('#pagecontent')
                    .html("<div id='nopage'>If you were looking for the answer to the question:" +
                    "<p style='font-size: 1.4em; text-align: center;'>What is the universe?</p> " +
                    "I can tell you it is 42. " +
                    "<p>But since you were looking for a page that does not exist, then I can tell you:</p>" +
                    "<p style='font-size: 2em; text-align: center;'>ERROR 404</p></div>")
                    .fadeIn(200);
            } else if (json.status === true) {
                displayPage(page,json.pageName,urlparam);
            } else {
                console.log(json);
                $('#pagecontent')
                    .html(json.msg)
                    .fadeIn(200);
            }
        }
    })
}

/**
 * Load page content by clicking on a menu section
 *
 * @param page
 * @param pagetoload
 * @param param
 */
var displayPage = function(page,pagetoload,param) {
    var stateObj = { page: pagetoload };
    var url = (param === false) ? "index.php?page="+page:"index.php?page="+page+"&"+param;
    var el = $('#pagecontent');
    jQuery.ajax({
        url: 'pages/'+pagetoload+'.php',
        type: 'GET',
        async: true,
        data: param,
        beforeSend: function() {
            loadingDiv(el);
        },
        complete: function () {
            removeLoading(el);
        },
        success: function(data){
            var json = jQuery.parseJSON(data);
            history.pushState(stateObj, pagetoload, url);

            el.hide().html(json);

            $("section").each(function() {
                $(this).fadeIn(200);
            });
            tinymcesetup();
            var callback = showPlugins;
            getPlugins(pagetoload, callback);
        }
    });
};

/**
 * Parse URL
 * @returns {Array}
 */
var parseurl = function() {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    vars = vars.slice(1,vars.length);
    vars = vars.join("&");
    return vars;
};

/**
 * Get URL parameters ($_GET)
 * @returns {{}}
 */
getParams = function() {
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
}

/**
 * Display loading animation during AJAX request
 * @param el: DOM element in which we show the animation
 */
function loadingDiv(el) {
    el
        .css('position','relative')
        .append("<div class='loadingDiv' style='width: 100%; height: 100%;'></div>")
        .show();
}

/**
 * Remove loading animation at the end of an AJAX request
 * @param el: DOM element in which we show the animation
 */
function removeLoading(el) {
    el.fadeIn(200);
    el.find('.loadingDiv')
        .fadeOut(1000)
        .remove();
}

/**
 * Responsive design part: adapt page display to the window
 */
function adapt() {
    var floatmenu = $('#float_menu');
    var topnav = $('.topnav');
    var sideMenu = $('.sideMenu');
    sideMenu.hide(); // Hide sideMenu
    floatmenu.hide(); // Hide Menu button
    $('.submenu').hide();

    // Get header width
    var headerwidth = $("#sitetitle").outerWidth() + topnav.outerWidth() + $("#login_box").outerWidth() + 10;

    if ($(window).width() <= headerwidth) {
        floatmenu.show();
        topnav.hide();
    } else {
        floatmenu.hide();
        topnav
            .css('display','inline-block')
            .show();
    }

    var height = $(window).height();
    var winWidth = $(window).width();
    $('#core').css('min-height',height+"px");

    var modal = $(".modalContainer");
    var modalWidth = modal.outerWidth();
    var modalMargin = (modalWidth<winWidth) ? modalWidth/2:0;
    var modalLeft = (modalWidth<winWidth) ? 50:0;
    modal
        .css({
            'margin-left':-modalMargin+'px',
            'left':modalLeft+'%'});

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