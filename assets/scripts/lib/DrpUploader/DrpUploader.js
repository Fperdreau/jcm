/**
 * File for DrpUploader jQuery-UI widget
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2017 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * DrpUploader is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * DrpUploader is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Constructor object.
 * Animate background (rightward moving linear-gradient)
 * @param el
 */
function AnimateBack(el) {
    this.el = el;
    this.interval = 0;
    this.gradient_percent = 0;
    this.interval_value = 5;
    this.time_interval = 50;
    this.timer = null;

    /**
     * Animate background
     */
    this.anim = function(){
        if(this.interval === 20) {
            this.interval = 0;
            this.gradient_percent = 0;
        }

        this.gradient_percent += this.interval_value;
        this.el.css('background', 'linear-gradient(to right, rgba(64,64,64,1) '+ this.gradient_percent+'%,rgba(0,0,0,0) 100%)');

        this.interval++;
    };

    /**
     * Start Animation
     */
    this.start = function() {
        if (this.timer === null) {
            this.timer = setInterval(
                (function(self) {
                    return function() {
                        self.anim();
                    };
                })(this),
                this.time_interval
            );
        }
    };

    /**
     * Stop animation
     */
    this.stop = function() {
        var self = this;
        if (self.timer !== null) {
            setTimeout(function () {
                clearInterval(self.timer);
                self.el.css('background-color', 'rgba(68,68,68,1)');
                self.timer = null;
            }, 1000);
        }
    };
}

// jQuery starts here
(function($){
    $.widget( "nmk.DrpUploader", {

        // Counter for Drag events
        _drag_counter: 0,

        // Uploader id
        _id: null,

        // Controller name
        _controller: null,

        // Related DOM elements
        // Input button
        _upl_btn: null,

        // Error message field
        _upl_errors: null,

        // Upload (hidden) input
        _upl_input: null,

        // Animator instance
        _animator: null,

        // Uploads list element
        _upl_list: null,

        _create: function() {
            if (this.element.data('nmk.DrpUploader') !== undefined) {
                return this;
            }

            // Set DOM elements
            this._setElements();

            // Add events handlers
            this._addEventHandlers();
        },

        /**
         * Set related DOM elements
         * @private
         * @return {void}
         */
        _setElements: function() {
            this._upl_btn = this.element.find('.upl_btn');
            this._upl_input = this.element.find('.upl_input');
            this._upl_errors = this.element.find('.upl_errors');
            this._animator = new AnimateBack(this.element);
            this._id = this.element.attr('id');
            this._controller = this.element.data('controller');
            this._upl_list = this.element.find('.upl_filelist');
        },

        /**
         * Add event handlers
         * @private
         * @return {void}
         */
        _addEventHandlers: function() {
            this._on(this.element, {
                dragenter: '_dragEnter',
                dragleave: '_dragLeave',
                dragover: '_dragOver',
                drop: '_getDrop',
                click: '_dispatch'
            });

            this._on(this._upl_btn, {
                click: '_onClick'
            });

            this._on(this.document.find('.del_upl'), {
                click: '_delUpload'
            });

            this._on(this._upl_input, {
                change: '_fireUpl'
            });

            this._on(this._upl_errors, {
                click: '_removeError'
            });
        },

        /**
         * Delegate event to another node
         * @param e: event
         * @private
         */
        _dispatch: function(e) {
            if ($(e.target).hasClass('del_upl')) {
                this._delUpload($(e.target));
            }
        },

        /**
         * Fire uploading process
         * @param e: event
         * @private
         */
        _fireUpl: function(e) {
            e.preventDefault();
            var fileInput = this._upl_input[0];
            for(var i = 0; i < fileInput.files.length; ++i){
                var data = new FormData();
                data.append('file[]', fileInput.files[i]);
                this._process(data);
            }
        },

        /**
         * Add "dragging" class to element when drag enter is detected
         * @param e: event
         * @private
         */
        _dragEnter: function(e) {
            e.stopPropagation();
            e.preventDefault();
            this._drag_counter++;
            this.element.addClass('dragging');
        },

        /**
         * Remove "dragging" class to element when drageleave is detected
         * @param e
         * @private
         */
        _dragLeave: function(e) {
            e.stopPropagation();
            e.preventDefault();
            this._drag_counter--;
            if (this._drag_counter === 0) {
                this.element.removeClass('dragging');
            }
        },

        /**
         * Drag over
         * @param e
         * @private
         */
        _dragOver: function(e) {
            e.stopPropagation();
            e.preventDefault();
        },

        /**
         * Get dragged file
         */
        _getDrop: function(e) {
            e.stopPropagation();
            e.preventDefault();

            var dt = e.dataTransfer || (e.originalEvent && e.originalEvent.dataTransfer);
            var files = e.target.files || (dt && dt.files);
            if (files) {
                for (var i = 0; i <  files.length; ++i){
                    var data = new FormData();
                    data.append('file[]', files[i]);
                    this._process(data);
                }
            }

            this.element.removeClass('dragging');
        },

        /**
         *
         * @private
         */
        _onClick: function() {
            this._upl_input.click();
        },

        /**
         * Process uploads
         * Animate uploader background while files are being uploaded. Send ajax request ($_FILES) and retrieve success or errors.
         * In case of success, show uploaded files in the files list. Otherwise, show error message.
         */
        _process: function(data) {
            var self = this;

            // Hide previous message
            self._upl_errors.hide();

            jQuery.ajax({
                type:'POST',
                url:'php/upload.php?controller=' + self._controller,
                headers:{'Cache-Control':'no-cache'},
                data: data,
                contentType:false,
                processData:false,
                beforeSend: self._animator.start(),
                complete: self._animator.stop(),
                async: true,
                success: function(response){
                    var result = jQuery.parseJSON(response);
                    var error = result.error;

                    var form = $('form#' + self._id);
                    if (error === true) {
                        if (form.length > 0) {
                            form.append(result.input);
                        } else {
                            console.warn('No form linked to the uploader');
                        }
                        self._upl_list.append(result.file_div);
                    } else {
                        self._upl_errors.addClass('warning').html(error).show();
                    }
                },
                error: function(response){
                    self._animator.stop();
                    self._upl_errors.addClass('warning').html(response.statusText).show();
                }
            });
        },

        /**
         * Process Ajax requests
         * @param data: serialized array
         * @param callback: callback function
         * @param url: path to the php file
         */
        _callAjax: function(data, callback, url) {
            url = (url === undefined) ? 'php/form.php' : url;
            var loadingBack = this._animator;
            jQuery.ajax({
                url: url,
                type: 'POST',
                async: true,
                data: data,
                beforeSend: loadingBack.start(),
                complete: loadingBack.stop(),
                success: function(data) {
                    var result = jQuery.parseJSON(data);
                    callback(result);
                },
                error: function() {
                    loadingBack.stop();
                }
            });
        },

        /**
         * Remove error message
         * @private
         */
        _removeError: function() {
            this._upl_errors.fadeOut().empty()
        },

        /**
         * Delete uploaded files
         * @param el: node
         * @private
         */
        _delUpload: function(el) {
            var self = this;
            var data = {del_upl: true, file_id: el.attr('id')};

            /**
             * Callback function
             * @param result: associate array:
             *      {
             *          status: boolean,  success or failure
             *          uplname: string   id of uploaded file
             *      }
             */
            var callback = function(result) {
                if (result.status === true) {
                    $('.upl_info#upl_' + result.uplname).remove();
                    $('.upl_link#' + result.uplname).remove();
                } else {
                    self._upl_errors
                        .html(result.msg)
                        .addClass('warning')
                        .show();
                }
            };

            this._callAjax(data, callback);
        }

    });

})(jQuery);

$(document).ready(function() {

    $(document).on('mouseover', '.upl_container', function() {
        $(this).DrpUploader();

    });
});