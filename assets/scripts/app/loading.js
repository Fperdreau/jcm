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
var tinymcesetup = function (selector) {
    if (selector === undefined) selector = "tinymce";
    tinyMCE.remove();
    window.tinymce.dom.Event.domLoaded = true;

    tinymce.init({
        mode: "textareas",
        editor_selector : selector,
        width: "100%",
        height: 300,
        plugins: [
            "advlist autolink lists charmap print preview hr spellchecker",
            "searchreplace wordcount visualblocks visualchars code fullscreen",
            "save contextmenu directionality template paste textcolor"
        ],
        content_css: "vendor/tinymce/tinymce/skins/lightgray/content.min.css",
        toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | " +
        "bullist numlist outdent indent | l      ink image | print preview media fullpage " +
        "| forecolor backcolor emoticons"
    });

    // Attribute random unique ID to selector if does not have one yet
    $('.' + selector).each(function() {
        if ($(this).attr('id') === undefined || $(this).attr('id').length === 0) {
            $(this).attr('id', 'tinymce_' + Math.round(new Date().getTime() + (Math.random() * 100)));
        }
        tinyMCE.execCommand("mceAddControl", true, $(this).attr('id'));
    })
};

/**
 * Load JCM calendar
 */
var loadCalendarSessions = function() {
    $('#ui-datepicker-div').remove();
    jQuery.ajax({
        url: 'php/router.php?controller=Calendar&action=getParams',
        type: 'POST',
        async: true,
        success: function (data) {
            var result = jQuery.parseJSON(data);
            inititdatepicker(result);
        }
    });
};

/**
 * Load JCM calendar
 */
var loadCalendarSubmission = function() {
    $('#ui-datepicker-div').remove();
    jQuery.ajax({
        url: 'php/router.php?controller=Calendar&action=getParams',
        type: 'POST',
        async: true,
        success: function (data) {
            var result = jQuery.parseJSON(data);
            init_submission_calendar(result);
        }
    });
};

/**
 * Load JCM calendar
 */
var loadCalendarAvailability = function() {
    var formid = $('#availability_calendar');
    if (formid.length>0 && formid !== undefined) {
        formid.css({'position':'relative', 'min-height':'200px'});
        jQuery.ajax({
            url: 'php/router.php?controller=Calendar&action=getParams',
            type: 'POST',
            async: true,
            beforeSend: function () {
                loadingDiv(formid);
            },
            complete: function () {
                removeLoading(formid);
            },
            success: function (data) {
                if (formid.hasClass('hasDatepicker')) {
                    formid.datepicker('destroy');
                }
                initAvailabilityCalendar(jQuery.parseJSON(data));
            }
        });
    }
};

/**
 * Parse url and get page content accordingly
 * @param page
 * @param urlparam
 */
function getPage(page, urlparam) {
    var params = getParams();
    if (page === undefined) {
        page = (params.page === undefined) ? 'home' : params.page;
    }

    urlparam = (urlparam === undefined) ? parseurl() : urlparam;
    urlparam = (urlparam === false || urlparam === "") ? false : urlparam;

    var el = $('main');
    params['getPage'] = page;

    jQuery.ajax({
        url: 'php/router.php?controller=Page&action=getPage&page=' + page,
        data: params,
        type: 'POST',
        async: true,
        beforeSend: function () {
            loadingDiv(el);
        },
        complete: function () {
            removeLoading(el);
        },
        success: function (data) {
            var json = jQuery.parseJSON(data);

            // Change url and push it to history
            var stateObj = { page: json.pageName };
            var url = (urlparam === false) ? "index.php?page=" + page : "index.php?page=" + page + "&" + urlparam;
            history.pushState(stateObj, json.pageName, url);
            displayPage(page, json);
        }
    });
}

var Editor = "CKEditor";

/**
 * Load WYSIWYG editor
 */
function loadWYSIWYGEditor () {
    var areas = $(document).find('textarea.wygiwym');
    $.each(areas, function (i, area) {
        if (CKEDITOR.instances[$(area).attr('id')]) {
            CKEDITOR.instances[$(area).attr('id')].destroy();
        }
        CKEDITOR.replace(area);
    });

    /*tinyMCE.remove();
    window.tinymce.dom.Event.domLoaded = true;
    tinymcesetup();*/
}

/**
 * Load page content by clicking on a menu section
 *
 * @param page
 * @param data
 */
var displayPage = function (page, data) {
    var plugins = data.plugins;

    pageTransition(data);

    // Display Plugins
    showPlugins(page, plugins);

    // Load WYSIWYG editor
    loadWYSIWYGEditor();

    // Load JCM calendar
    loadCalendarSessions();

    // Load availability calendar
    loadCalendarAvailability();

    // Load submission calendar
    loadCalendarSubmission();
};

/**
 * Animate page transition
 * @param content
 * @returns {boolean}
 */
function pageTransition(content) {
    var container = $('#hidden_container');
    var current_content = container.find('#current_content');

    if (container.find('#next_content').length === 0) {
        container.append('<div id="next_content"></div>');
        renderSection(current_content, content);
        return true;
    }

    var next_content = container.find('#next_content');
    renderSection(next_content, content);

    current_content.animate({'margin-left': '-100%', 'opacity': 0}, 1000, function() {
        var next_content = $(this).siblings('#next_content');
        next_content.attr('id', 'current_content');
        next_content.after('<div id="next_content"></div>');
        $(this).remove();
    });
}

/**
 * Render page content and header
 * @param section
 * @param content
 */
function renderSection(section, content) {
    var defaultHtml = '<div class="wrapper"><div id="section_title"></div><div id="section_content"></div></div>';
    section.html(defaultHtml);
    section.find('#section_content').html(content.content);
    section.find('#section_title').html(content.header);
}

/**
 * Parse URL
 * @returns {Array}
 */
var parseurl = function () {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    vars = vars.slice(1, vars.length);
    vars = vars.join("&");
    return vars;
};

/**
 * Get URL parameters ($_GET)
 * @returns {{}}
 */
getParams = function () {
    var url = window.location.href;
    var splitted = url.split("?");
    if (splitted.length === 1) {
        return {};
    }
    var paramList = decodeURIComponent(splitted[1]).split("&");
    var params = {};
    for (var i = 0; i < paramList.length; i++) {
        var paramTuple = paramList[i].split("=");
        params[paramTuple[0]] = paramTuple[1];
    }
    return params;
};

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

$( document ).ready(function () {

    $(window).resize(function () {
        adapt();
    });

    $('body').ready(function () {
        // Automatically parse url and load the corresponding page
        getPage();
        adapt();
    });

});
