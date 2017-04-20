/**
 * File for leanModal plugin:
 *
 * Original license:
 * leanModal v1.1 by Ray Stone - http://finelysliced.com.au
 * Dual licensed under the MIT and GPL
 *
 * Modified version:
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
 * Note on the modified version:
 * Make this plugin working with any kind of DOM element (not only <a> tags) and made it more responsive-design friendly
 */

(function($){
    $.fn.extend({
        leanModal:function(options){
            var defaults={
                top:100,
                overlay:0.5,
                closeButton: '.modal_close',
                load: true,
                callback: undefined
            };

            var overlay=$("<div id='lean_overlay'></div>");

            if ($('#lean_overlay').length === 0) {
                $("body").append(overlay);
            }

            options = $.extend(defaults,options);
            return this.click(function(e){

                var o=options;
                var overlayEL = $('#lean_overlay');
                var modal_id = $(this).data("modal");
                modal_id = (modal_id === undefined) ? '#modal': modal_id; // Define default DOM target
                var section = $(this).data('section');

                overlayEL.click(function(){
                    close_modal(modal_id);
                });

                $(o.closeButton).click(function(){
                    close_modal(modal_id);
                });

                overlayEL
                    .css({"display":"block", opacity:0})
                    .fadeTo(200,o.overlay);

                if (o.load === true) {
                    load_section($(this).data(), o.callback);
                }

                e.preventDefault();
            });
        }
    });
})(jQuery);

/**
 * Close modal when clicking outsize the window or on the close button
 * @param modal_id
 */
function close_modal(modal_id){
    modal_id = (modal_id === undefined) ? '.modalContainer' : modal_id;
    $("#lean_overlay").fadeOut(200);
    $(modal_id).css({"display":"none"});
}

/**
 * Show the targeted modal section and hide the others
 * @param section: section id
 * @param modal_id: modal container id
 */
function show_section(section, modal_id) {
    modal_id = modal_id === undefined ? 'modal' : modal_id;
    var modal = $(".modalContainer#" + modal_id);
    var modalContainer = modal.find('.popupBody');
    var sections = $(".modalContainer#" + modal_id + ' .modal_section');

    // Tag previous section
    var previous_section = null;
    sections.each(function() {
        if ($(this).is(':visible') || $(this).hasClass('current_section')) {
            $(this).addClass('previous_section');
            $(this).removeClass('current_section');
            previous_section = $(this).attr('id');
        } else if ($(this).hasClass('previous_section')) {
            $(this).removeClass('previous_section');
        }
    });

    // Remove back button
    if (modal.find('.back_btn').length > 0) {
        modal.find('.back_btn').remove();
    }

    // Hide all sections
    sections.hide();

    // Add back button
    if (previous_section !== null && previous_section.length > 0) {
        var back_button = "<div class='back_btn' data-prev='" + previous_section + "'></div>";
        modal.find('.float_buttons_container').prepend(back_button)
    }

    // Show target section
    if (modalContainer.children('.modal_section#' + section).length > 0) {
        transition(modal, modalContainer.children('.modal_section#' + section));
        modalContainer.children('.modal_section#' + section).addClass('current_section');
    }
}

/**
 * Render loading layout on top of modal window
 * @param modal: modal selector
 */
function animate_before(modal) {
    if (!modal.is(':visible')) {
        var start_width = 50;
        var start_height = 50;
        modal.css({
            height: start_height + 'px',
            width: start_width + 'px'
        });
    }
    loadingDiv(modal);
}

/**
 * Animate modal loading
 * @param modal
 * @param section
 */
function transition(modal, section) {

    // Add blank layout
    modal.append("<div class='blankDiv'></div>");

    // Get dimensions
    var section_dim = realDim(section);
    var section_content_dim = realDim(section.find('.popupContent'));
    var section_header_dim = realDim(section.find('.popupHeader'));
    var modal_dim = realDim(modal);

    var win_height = $(window).height();
    var win_width = $(window).width();
    var modal_max_height = Math.round(0.9*win_height) - section_header_dim['height'];
    var auto_height = Math.min(Math.max(section_dim['height'], section_content_dim['height']), modal_max_height);

    // Set max-height of section content
    modal.find('.popupContent').css({
        'overflow': 'hidden',
        'max-height': auto_height + 'px'
    });

    modal
        // Set modal position
        .css({
            "display": "block",
            "position": "absolute",
            "z-index": 11000,
            "left": Math.round( (0.5 * win_width) - (0.5*modal_dim.width) ) + 'px',
            "top": Math.round( (0.5 * win_height) - (0.5*modal_dim.height) ) + "px"
        })
        // Show modal
        .fadeTo(200, 1)

        // Animate transition
        .animate({
            'top': Math.round(0.5 * (win_height - auto_height)) + $(document).scrollTop() + 'px',
            'left': Math.round(0.5 * (win_width - parseInt(modal_dim['max-width']))) + 'px',
            'width': modal_dim['max-width'],
            'margin-left': 0
        }, 300, function() {
            $(this).find('.popupContent').css('overflow-y', 'auto');

            // Show section
            section.show();

            // Remove blank layer
            $(this).find('.blankDiv').css({'opacity': 0,'transition':'.15s ease-in-out'}).remove();
        });
}

/**
 * Get size of hidden objects
 * @param obj (DOM element)
 * @returns {*}
 */
function realDim(obj) {
    var clone = obj.clone();
    clone.css({"visibility": "hidden", 'max-height': 'none'});
    $('body').append(clone);
    var data = {
        height: clone.outerHeight(true),
        width: clone.outerWidth(),
        'max-width': clone.css('max-width'),
        'max-height': clone.css('max-height')
    };
    clone.remove();
    return data;
}

/**
 * load section content and remove previous content if section already exists
 * @param data: section information
 * @param callback: callback function
 */
function load_section(data, callback) {
    var modal_id = (data.modal === undefined) ? 'modal' : data.modal; // Define default modal target
    var modalContainer = $('.modalContainer#' + modal_id).find('.popupBody');
    var target_section = modalContainer.find('.modal_section#' + data.section);

    // Remove section if it already exists
    if (target_section.length > 0) {
        target_section.remove();
    }

    data.get_modal = true;
    jQuery.ajax({
        url: 'php/form.php',
        data: data,
        type: 'post',
        beforeSend: function () {
            animate_before($('.modalContainer#' + modal_id))
        },
        complete: function() {
            removeLoading($('.modalContainer#' + modal_id));
        },
        success: function(json) {
            var result = jQuery.parseJSON(json);
            modalContainer.append(result);
            show_section(data.section, modal_id);
            if (callback !== undefined) {
                callback();
            }
        },
        error: function () {
            removeLoading($('.modalContainer#' + modal_id));
        }
    });
}

/**
 * Go to previous section
 * @param selector_id
 * @param modal_id
 */
function go_to_previous(selector_id, modal_id) {
    modal_id = modal_id === undefined ? 'modal' : modal_id;
    var modalContainer = $('.modalContainer#' + modal_id).find('.popupBody');
    var sections;
    if (selector_id === undefined) {
        sections = $(".modalContainer#" + modal_id + ' .previous_section');
    } else {
        sections = $('.modalContainer#' + modal_id + ' .modal_section#' + selector_id);
    }
    if (sections.length === 0) return false;
    show_section(sections.attr('id'), modal_id);
}

/**
 * load section content if it does not exist yet, otherwise show it
 * @param data: section information
 */
function go_to_section(data) {
    var modal_id = (data.modal === undefined) ? 'modal' : data.modal; // Define default DOM target
    var modalContainer = $('.modalContainer#' + modal_id).find('.popupBody');
    var target_section = modalContainer.find('.modal_section#' + data.section);
    if (target_section.length > 0) {
        show_section(data.section, modal_id);
    } else {
        data.get_modal = true;
        jQuery.ajax({
            url: 'php/form.php',
            data: data,
            type: 'post',
            complete: function() {
                removeLoading($('.modalContainer#' + modal_id));
            },
            success: function(json) {
                var result = jQuery.parseJSON(json);
                modalContainer.append(result);
                show_section(data.section, modal_id);
            }
        });
    }
}

/**
 * Trigger modal window
 * @param el
 * @param load: load content on click
 * @param callback: callback function call on content load
 */
function trigger_modal(el, load, callback) {
    if (load === undefined) load = true;
    if (el.data('leanModal') === undefined) {
        el.leanModal({top : 50, overlay : 0.6, closeButton: ".modal_close", load: load, callback: callback});
        el.data('leanModal', true);
        el.click();
    }
}

/**
 * Check if selector is rendered within a modal window
 * @param el: selector
 * @param modal_id: id of modal window ('modal' by default)
 * @returns {boolean}
 */
function in_modal(el, modal_id) {
    modal_id = (modal_id === undefined) ? 'modal' : modal_id; // Define default DOM target
    return $('.modalContainer#' + modal_id).find(el).length > 0;

}