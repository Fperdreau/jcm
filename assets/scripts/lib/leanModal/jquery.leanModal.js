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

    /**
     * modalTrigger widget
     *
     *
     */
    $.widget( "nmk.modalTrigger", {
        options: {
            top: 100,
            overlay: 0.5,
            closeButton: '.modal_close',
            load: true,
            callback: null,
            modal_id: "#modal",
            container: '.modalContainer'
        },

        _window: null,

        /**
         * Constructor
         * @private
         */
        _create: function() {

            if (this.element.data('leanModal') !== undefined) {
                return this;
            } else {
                this.element.data('leanModal', true);
            }

            var modal_id = this.element.data("modal");
            this._setOption('modal_id', (modal_id === undefined) ? '#modal' : modal_id); // Define default DOM target

            // Bind event to handler
            this._on(this.element, {
                click: "onClick" // Note: function name must be passed as a string!
            });

            return this;
        },

        /**
         * Set modal window
         * @private
         */
        setWindow: function() {
            this._window = $(this.options.container + this.options.modal_id);
            if (this._window.data('nmkModalWindow') === undefined) {
                this._window.modalWindow();
                this._window.modalWindow('option', this.options);
                this._window.modalWindow('setData', this.element.data());
            }
        },

        /**
         * Get modal window selector
         * @returns {null}
         */
        getWindow: function() {
            return this._window;
        },

        /**
         * Add overlay to document
         * @private
         */
        _addOverlay: function() {
            // Add overlay
            var overlay = $("<div id='lean_overlay'></div>");

            if ($('#lean_overlay').length === 0) {
                $("body").append(overlay);
            }
        },

        /**
         * Make overlay visible
         */
        showOverlay: function() {
            var overlayEL = $('#lean_overlay');
            if (overlayEL.length > 0 && !overlayEL.is(':visible')) {
                overlayEL
                    .css({"display":"block", opacity:0})
                    .fadeTo(200, this.options.overlay);
            }
        },

        /**
         * Click handler
         * @param e
         */
        onClick: function(e) {
            e.preventDefault();

            // Add overlay to document
            this._addOverlay();

            this.showOverlay();

            // Load window content
            this._loadContent();
        },

        /**
         * Close linked window
         */
        close: function() {
            if (this._window !== null) {
                this._window.modalWindow('close');
            }
        },

        /**
         * Load window content from AJAX call
         * @private
         * @returns void
         */
        _loadContent: function() {
            if (this.options.load === true) {
                this._window.modalWindow('loadContent', this.element.data());
            }
        },

        /**
         * _setOptions is called with a hash of all options that are changing
         */
        _setOptions: function () {
            // _super and _superApply handle keeping the right this-context
            this._superApply(arguments);
        },

        /**
        /* _setOption is called for each individual option that is changing
         */
        _setOption: function (key, value) {
            this._super(key, value);
        }

    });


    /**
     * Modal Window widget
     */
    $.widget( "nmk.modalWindow", {
        options: {
            top: 100,
            closeButton: '.modal_close',
            callback: null,
            headerHeight: 35,
            footerHeight: 55
        },

        _data: {},

        /**
         * Constructor
         * @private
         */
        _create: function() {
            // Bind actions to close buttons
            this._bind_close();
        },

        /**
         * Setter for data
         * @param data
         * @private
         */
        setData: function(data) {
            this._data = $.extend(this._data, data);
        },

        /**
         * Bind actions to close buttons
         * @private
         */
        _bind_close: function() {
            // Bind close buttons
            var overlay = $("<div id='lean_overlay'></div>");

            var modal_id = this.options.modal_id;
            var obj = this;

            overlay.click(function(){
                obj.close(modal_id);
            });

            $(this.options.closeButton).click(function(){
                obj.close(modal_id);
            });
        },

        /**
         * Load section content into modal window
         * @param data
         */
        loadContent: function(data) {
            delete data['nmkModalTrigger'];
            this._load_section(data, this.options.callback);
        },

        /**
         * Close modal window
         */
        close: function() {
            $("#lean_overlay").fadeOut(200);
            $(this.element).css({"display":"none"});
        },

        /**
         * load section content and remove previous content if section already exists
         * @param data: section information
         * @param callback: callback function
         */
        _load_section: function(data, callback) {
            var modalContainer = this.element.find('.popupBody');
            var target_section = modalContainer.find('.modal_section#' + data.section);

            // Remove section if it already exists
            if (target_section.length > 0) {
                target_section.remove();
            }

            this._loadData(data, this, modalContainer, this._renderModalContent);
        },

        /**
         * Render modal content
         */
        _renderModalContent: function(data, json, obj, modalContainer) {
            jQuery.ajax({
                url: 'php/router.php?controller=Modal&action=render',
                data: json,
                type: 'post',
                async: true,
                beforeSend: function () {
                    obj._animate_before();
                },
                complete: function() {
                    obj._removeLoading();
                },
                success: function(json) {
                    var result = jQuery.parseJSON(json);
                    modalContainer.append(result);
                    obj.show_section(data.section, obj.options.modal_id);

                    // Call callback function if specified
                    if (obj.options.callback !== null) {
                        obj.options.callback();
                    }
                },
                error: function () {
                    obj._removeLoading();
                }
            });
        }, 

        /**
         * load data from provided url
         */
        _loadData: function(data, obj, modalContainer, callback) {
            jQuery.ajax({
                url: data.url,
                data: data,
                type: 'post',
                async: true,
                beforeSend: function () {
                    obj._animate_before();
                },
                complete: function() {
                    obj._removeLoading();
                },
                success: function(json) {
                    var result = jQuery.parseJSON(json);

                    // Call callback function if specified
                    callback(data, result, obj, modalContainer);
                    
                },
                error: function () {
                    obj._removeLoading();
                }
            });
        },

        /**
         * Render loading layout on top of modal window
         */
        _animate_before: function() {
            if (!this.element.is(':visible')) {
                var start_width = 150;
                var start_height = 150;
                var top = ($(window).height() - start_height) * 0.5;
                var left = ($(window).width() * 0.5) - start_width;
                this.element.css({
                    height: start_height + 'px',
                    width: start_width + 'px',
                    overflow: 'hidden',
                    'margin-left': 0,
                    top: top + 'px',
                    left: left + 'px'
                });
            }
            this._addLoading();
        },

        /**
         * Display loading animation during AJAX request
         */
        _addLoading: function() {
            this.element
                .css('position','relative')
                .append("<div class='loadingDiv' style='width: 100%; height: 100%;'></div>")
                .show();
            this.element.find('.loadingDiv').css('z-index', 12000);
        },

        /**
         * Remove loading animation at the end of an AJAX request
         */
        _removeLoading: function() {
            this.element
                .fadeIn(200)
                .find('.loadingDiv')
                .fadeOut(1000)
                .remove();
        },

        /**
         * Show the targeted modal section and hide the others
         * @param section: section id
         */
        show_section: function(section) {
            var modalContainer = this.element.find('.popupBody');

            // Get section
            var sections = this.element.find('.modal_section');

            // Tag previous section
            var previous_section = this._tagSections(sections);

            // Hide all sections
            sections.hide();

            // Hide/Show back button
            this._toggleBackBtn(previous_section);

            // Show target section
            if (modalContainer.children('.modal_section#' + section).length > 0) {

                this._transition(section);

                this._setCurrentSection(section);
            }
        },

        /**
         * Hide/Show back button of modal window provided there is a section tagged as "previous"
         * @param previous_section: previous section selector
         * @private
         */
        _toggleBackBtn: function(previous_section) {
            if (this.element.find('.back_btn').length > 0) {
                this.element.find('.back_btn').remove();
            }

            // Add back button
            if (previous_section !== null && previous_section.length > 0) {
                var back_button = "<div class='back_btn' data-prev='" + previous_section + "'></div>";
                this.element.find('.float_buttons_container').prepend(back_button)
            }
        },

        /**
         * Tag sections has previous or current
         * @param sections
         * @returns {*}
         * @private
         */
        _tagSections: function(sections) {
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
            return previous_section;
        },

        /**
         * Tag current section
         * @param section_id
         * @private
         */
        _setCurrentSection: function(section_id) {
            this.element.find('.modal_section#' + section_id).addClass('current_section');
        },

        /**
         * Get Window and section dimensions
         * @private
         */
        _resize: function(section) {

            // 1. Get dimensions
            var dimensions = this._getDimensions(section);

            // 2. Compute modal window's maximum height and adjusted height
            var modal_max_height = Math.round(0.9*dimensions['window'].height) - dimensions['header'].height;

            // 3. Compute new maximum height of modal window
            var new_max_height = Math.min(dimensions['section'].height, modal_max_height) + (this.options['headerHeight'] + this.options['footerHeight']);

            // 4. Compute body height
            var body_max_height = new_max_height - (this.options['headerHeight'] + this.options['footerHeight']);

            // Set max-height of section content
            this.element.find('.popupContent').css({
                'max-height': body_max_height + 'px'
            });

            dimensions['new_max_height'] = new_max_height;
            dimensions['body_max_height'] = body_max_height;

            return dimensions;
        },

        /**
         * Get window and section dimensions
         * @param section
         * @returns {Object}
         * @private
         */
        _getDimensions: function(section) {
            return {
                section: this._realDim(section),
                content: this._realDim(section.find('.popupContent')),
                header: this._realDim(section.find('.popupHeader')),
                footer: this._realDim(section.find('.modal_buttons_container')),
                modal: this._realDim(this.element),
                window: {width: $(window).width(), height: $(window).height()}
            };
        },

        /**
         * Animate modal loading
         * @param section_id
         */
        _transition: function(section_id) {

            // Get section selector
            var section = this.element.find('.modal_section#' + section_id);

            // Add blank layout
            this.element.append("<div class='blankDiv'></div>");

            // Resize modal window
            var dimensions = this._resize(section);

            // Animate transition
            this.element
                // Set modal position
                .css({
                    "display": "block",
                    "position": "absolute",
                    "z-index": 11000,
                    "left": Math.round( (0.5 * dimensions['window'].width) - (0.5*dimensions['modal'].width) ) + 'px',
                    "top": Math.round( (0.5 * dimensions['window'].height) - (0.5*dimensions['modal'].height) ) + "px"
                })

                // Show modal
                .fadeTo(200, 1)

               // Animate transition
                .animate({
                    'top': Math.round(0.5 * (dimensions['window'].height - dimensions['new_max_height'])) + $(document).scrollTop() + 'px',
                    'left': Math.round(0.5 * (dimensions['window'].width - parseInt(dimensions['modal']['max-width']))) + 'px',
                    'width': dimensions['modal']['max-width'],
                    'height': dimensions['new_max_height'] + 'px',
                    'margin-left': 0
                }, 300, function() {
                    $(this).find('.popupContent').css('overflow-y', 'auto');

                    // Show section
                    section.show();

                    // Remove blank layer
                    $(this).find('.blankDiv').css({'opacity': 0,'transition':'.15s ease-in'}).remove();
                });
        },

        /**
         * Get size of hidden objects
         * @param obj (DOM element)
         * @returns {*}
         */
        _realDim: function(obj) {
            var clone = obj.clone();
            clone.css({
                position:   'absolute',
                visibility: 'hidden',
                display:    'block'
            });
            $('body').append(clone);
            var data = {
                height: clone.outerHeight(),
                width: clone.outerWidth(),
                'max-width': clone.css('max-width'),
                'max-height': clone.css('max-height')
            };
            clone.remove();
            return data;
        },

        /**
         * Go to previous section
         * @param selector_id
         */
        go_to_previous: function(selector_id) {
            var section;
            if (selector_id === undefined) {
                section = this.element.find('.previous_section');
            } else {
                section = this.element.find('.modal_section#' + selector_id);
            }
            if (section.length === 0) return false;
            this.show_section(section.attr('id'));
        },

        /**
         * load section content if it does not exist yet, otherwise show it
         * @param data: section information
         */
        go_to_section: function(data) {
            var modalContainer = this.element.find('.popupBody');
            var target_section = modalContainer.find('.modal_section#' + data.section);
            if (target_section.length > 0) {
                this.show_section(data.section);
            } else {
                this._load_section(data);
            }
        },

        /**
          *  _setOptions is called with a hash of all options that are changing
          */
        _setOptions: function () {
            // _super and _superApply handle keeping the right this-context
            this._superApply(arguments);
        },

        /**
         * _setOption is called for each individual option that is changing
         */
        _setOption: function (key, value) {
            this._super(key, value);
        }

    });

})(jQuery);

/**
 * Trigger modal window
 * @param el
 * @param load: load content on click
 * @param callback: callback function call on content load
 */
function trigger_modal(el, load, callback) {
    if (load === undefined) load = true;
    el.modalTrigger();
    el.modalTrigger('option', {top : 50, overlay : 0.6, closeButton: ".modal_close", load: load, callback: callback});
    el.modalTrigger('setWindow');
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