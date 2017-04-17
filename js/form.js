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

/**
 * Collection of useful functions to process forms
 */

/**
 * Process a form
 * @param el: DOM ID of the form
 * @returns {boolean}
 * @param callback: callback function to execute after the form has been processed
 * @param url: path to the php-side file
 * @param timing: duration of feedback message
 */
var processForm = function(el,callback,url,timing) {
    if (!checkform(el)) { return false;}
    var data = el.serializeArray();

    // Find tinyMCE textarea and gets their content
    var tinyMCE_el = el.find('.tinymce');
    if (tinyMCE_el.length > 0 && tinyMCE_el !== undefined) {
        tinyMCE_el.each(function() {
            var id = $(this).attr('id');
            var input_name = $(this).attr('name');
            var content = tinyMCE.get(id).getContent();
            data = modArray(data, input_name, content);
        })
    }
    url = el.attr('action');
    processAjax(el,data,callback,url,timing);
};

/**
 * Add loading animation to submit button
 * @param el
 */
function loadingButton(el) {
    el.addClass('processform_working');
}

function resetButton(el) {
    setTimeout(function() {
        el.removeClass('processform_success processform_working processform_failure');
    }, 1000);
}

/**
 * Loading animation for submit buttons
 * @param el
 */
function removeLoadingButton(el) {
    el.removeClass('processform_working');
}

/**
 * Process Ajax requests
 * @param formid
 * @param data
 * @param callback: callback function
 * @param url: path to the php file
 * @param timing: duration of feedback message
 */
var processAjax = function(formid, data, callback, url, timing) {
    var btn = formid.find('input[type="submit"], .processform');
    jQuery.ajax({
        url: url,
        type: 'POST',
        async: true,
        data: data,
        beforeSend: function () {
            if (btn.length > 0) {
                loadingButton(btn);
            } else {
                loadingDiv(formid);
            }
        },
        complete: function () {
            if (btn.length > 0) {
                removeLoadingButton(btn);
            } else {
                removeLoading(formid);
            }
        },
        success: function (data) {
            callback = (callback === undefined) ? false: callback;
            validsubmitform(formid, data, callback, timing, btn);
        },
        error: function() {
            if (btn.length > 0) {
                removeLoadingButton(btn);
            } else {
                removeLoading(formid);
            }
        }
    });
};

/**
 * Temporarily replace a form by a feedback message
 * @param el: DOM element
 * @param data: feedback to show
 * @param callback: callback function (what to do after the feedback message. By default, we simply re-display the form
 * as it was)
 * @param timing: duration of feedback
 * @param btn
 */
var validsubmitform = function (el, data, callback, timing, btn) {
    el = (el === undefined) ? $('body') : el;
    var result = jQuery.parseJSON(data);
    callback = (callback === undefined) ? false : callback;
    timing = (timing === undefined) ? 2000 : timing;
    if (btn !== undefined && btn.length > 0) {
        removeLoadingButton(btn);
        if (result.status) {
            btn.addClass('processform_success');
        } else {
            btn.addClass('processform_failure');
        }
    }

    // Display feedback message and/or run callback function
    if (result.msg !== undefined) {
        showFeedBack(el, result.status, result.msg);
        setTimeout(function() {
            removeFeedBack(el);

            if (btn !== undefined && btn.length > 0) resetButton(btn);

            // Run callback function
            if (callback !== false) {
                callback(result);
            }
        }, timing);
    } else {

        if (btn !== undefined && btn.length > 0) resetButton(btn);
        removeLoading(el);
        // Run callback function
        if (callback !== false) {
            callback(result);
        }
    }
};


/**
 * Format feedback message
 * @param status
 * @param text
 * @returns {boolean}
 */
function format_msg(status, text) {
    // Format msg
    var msg = false;
    if (status === true) {
        msg = (text === undefined) ? "<p class='sys_msg success'>DONE!</p>" :
        "<p class='sys_msg success'>" + text + "</p>";
    } else if (status !== undefined) {
        msg = (text === undefined) ? "<p class='sys_msg warning'>Oops, something has gone wrong!</p>" :
        "<p class='sys_msg warning'>" + text + "</p>";
    }
    return msg;
}

/**
 * Remove feedback layer and message
 * @param el
 */
function removeFeedBack(el) {

    fade_inputs(el, false);
    el.find('.feedbackForm')
        .fadeIn(500)
        .delay(200, function() {
            $(this).remove();
        });

}

/**
 * Fades form's inputs
 * @param el
 * @param op
 */
function fade_inputs(el, op) {

    if (op === true) {
        el.children('input, select, .form-group').each(function() {
            $(this).animate({opacity: 0.2});
        });
    } else {
        el.children('input, select, .form-group').each(function() {
            $(this).animate({opacity: 1.0}, 200);
        });
    }
}

/**
 * Display feedback over a form
 * @param el: form DOM
 * @param status
 * @param msg
 */
function showFeedBack(el, status, msg) {
    // Append feedback layer
    var width = el.width();
    var height = el.height();
    var text = format_msg(status, msg);

    el.append("<div class='feedbackForm'></div>");
    var feedbackForm = $('.feedbackForm');

    fade_inputs(el, true);
    feedbackForm
        .css({left: 0, width: width + 'px', height: height + 'px'})
        .html(text);

    var text_el = feedbackForm.find('.sys_msg');
    text_el
        .empty()
        .css({left: 0, display: 'none', width: 0.8*width + 'px'})
        .html(msg)
        .fadeIn(300);
}

/**
 * Check if tinyMCE editor is active
 * @param editor_id
 * @param el
 * @returns {boolean}
 */
var is_editor_active = function(el, editor_id){
    var editor;
    if(typeof tinyMCE === 'undefined'){
        return false;
    }

    if( typeof editor_id === 'undefined' ){
        editor = tinyMCE.activeEditor;
    }else{
        editor = tinyMCE.EditorManager.get(editor_id);
    }

    if (editor === null || el === undefined || !el.hasClass('tinymce') || tinyMCE.get(el.attr('id')) === null) {
        return false;
    }

    return !editor.isHidden();
};

/**
 * Check whether every required fields have been filled up correctly
 * @param el: DOM element
 * @param btn: submit button
 * @returns {boolean}
 */
var checkform = function(el, btn) {
    btn = (btn === undefined) ? el.find("input[type=submit]:focus") : btn;

    var valid = true;
    el.closest('.inputFeedback').remove();
    var msg = "* Required";

    el.find('input,select,textarea').not('input[type="submit"]').each(function () {
        $(this).removeClass('wrongField');
        var thisField = true;

        // Check if required fields have been filled in
        if ($(this).is(':visible') && $(this).prop('required') && $.trim($(this).val()).length === 0){
            thisField = false;
        }

        if ($(this).prop('required') && is_editor_active($(this)) && tinyMCE.get($(this).attr('id')).getContent().length === 0) {
            tinymce.execCommand('mceFocus',false,'consent');
            thisField = false;
        }

        if ($(this).attr('maxlength') && $(this).val().length > $(this).attr('maxlength')) {
            thisField = false;
            msg = 'Exceeds Maximum length ('+$(this).attr('maxlength')+')';
        }

        if ($(this).attr('minlength') && $(this).val().length > $(this).attr('minlength')) {
            thisField = false;
            msg = 'Too short! (minimum: '+$(this).attr('minlength')+')';
        }

        // Check if provided email is valid
        if ($(this).val() !== null && $(this).val().length > 0 && $(this).attr('type') === 'email' && !checkemail($(this).val())) {
            msg = "Invalid email";
            thisField = false;
        }

        if (!thisField) {
            $(this).addClass('wrongField');
            $(this).parent('div').append("<div class='inputFeedback' style='display: none;'></div>");
            $(this).parent('div').find('.inputFeedback').animate({width:'toggle'});
        }

        valid = (!thisField) ? thisField:valid;

    });

    // Check if at least one checkbox by checkbox name is checked
    var checkbox = el.find('input[type="checkbox"]');
    var name_map = {};
    var ok = true;
    checkbox.each(function() {  // first pass, create name mapping
        var name = this.name;
        if (name_map[name] === undefined) {
            ok = el.find("input[name='"+name+"']:checked").length > 0;
            if (!ok) {
                el.find("input[name='"+name+"']")
                    .last()
                    .addClass('wrongField')
                    .after("<div class='inputFeedback' style='display: none;'>*</div>")
                    .next('.inputFeedback').animate({width:'toggle'});
            }
            name_map[name] = name;
        }
    });
    valid = valid && ok;

    // Check if form include password confirmation
    var conf_password = el.find("input[name=conf_password]");
    if (conf_password.length > 0) {
        var password = el.find("input[name=password]").val();
        if (password !== conf_password.val()) {
            conf_password.addClass('wrongField');
            msg = "Passwords must match";
            valid = false;
        }
    }

    // Show feedback message next to the submit button
    if (!valid) {
        el.find('.feedbackSubmit').remove();
        btn
            .before("<div class='feedbackSubmit' style='display: none;'>"+msg+"</div>");
        el.find('.feedbackSubmit').animate({width:'toggle'},350);
    }

    // Set focus on the first empty element
    el.find('input.wrongField:first').focus();
    return valid;
};

/**
 * Check whether the provided email is valid
 * @param email
 * @returns {boolean}
 */
var checkemail = function (email) {
    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    return pattern.test(email);
};

/**
 * Show a feedback after having processed the form
 * @param message: feedback
 * @param selector: feeback div
 * @returns {boolean}
 */
var showfeedback = function (message, selector) {
    var el = (typeof selector === "undefined") ? ".feedback":".feedback#"+selector;
    $(el)
        .html(message)
        .fadeIn(1000)
        .delay(3000)
        .fadeOut(1000);
    return false;
};

/**
 * Modify value of an array element
 * @param data: serialized array
 * @param prop: element
 * @param value: new value
 * @returns {*}: new serialized array
 */
function modArray(data,prop,value) {
    var i;
    for (i = 0; i < data.length; ++i) {
        if (data[i].name === prop) {
            data[i].value = value;
            break;
        }
    }
    return data;
}

$(document).ready(function () {
    $('body')
        .on('click','.processform', function (e) {
            e.preventDefault();
            var input = $(this);
            var form = input.length > 0 ? $(input[0].form) : $();
            processForm(form);
        })

        .on('blur', 'input, select, textarea', function(e) {
            if ($(this).is(':valid') && $(this).val() !== null && $(this).val().length > 0) {
                var inputFeedback = $(this).siblings('.inputFeedback');
                if (inputFeedback !== undefined) {
                    inputFeedback.fadeOut(200);
                }
                $(this).removeClass('wrongField');
            }
        })

        .on('click', '.feedbackForm', function() {
            removeFeedBack($(this).parent('form'));
        });
});
