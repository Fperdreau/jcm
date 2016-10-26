/**
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2016 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * passwordChecker is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * passwordChecker is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with passwordChecker.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * passwordChecker plugin
 * @description: This plugin test password strength and nicely displays
 * the result to the user while he/she is typing.
 */

(function($){
    $.fn.extend({
        passwordChecker:function() {

            var $div = $("<div class='passstrength'><div class='passwordChecker_bar'></div><div class='passwordChecker_text'></div></div>");
            var $el = this;
            var position = $el.position();
            return this.each(function() {
                if ($el.siblings('.passstrength').length==0) {
                    $el.parent('.form-group').append($div);
                }
                $el.on('keyup', function (e) {
                    var el = $(this).next('.passstrength');
                    var bar = el.find('.passwordChecker_bar');
                    var text = el.find('.passwordChecker_text');
                    bar.attr('class','passwordChecker_bar');
                    text.attr('class', 'passwordChecker_text');
                    el.css({
                        position:'absolute',
                        left:position.left,
                        top:position.top,
                        width: $el.outerWidth()
                    });
                    var strongRegex = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
                    var mediumRegex = new RegExp("^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$", "g");
                    var enoughRegex = new RegExp("(?=.{6,}).*", "g");
                    if (false == enoughRegex.test($(this).val())) {
                        bar.addClass('weak');
                        text.html('Too short');
                        text.addClass('weak')
                    } else if (strongRegex.test($(this).val())) {
                        bar.addClass('strong');
                        text.html('Strong');
                        text.addClass('strong')
                    } else if (mediumRegex.test($(this).val())) {
                        bar.addClass('medium');
                        text.html('Medium');
                        text.addClass('medium')
                    } else {
                        bar.addClass('weak');
                        text.html('Weak');
                        text.addClass('weak')
                    }
                    return true;
                })
            })
        }
    })
})(jQuery);


$(document).ready(function() {
    $('body')

        // Bind leanModal to triggers
        .on('focus',".passwordChecker",function(e) {
            e.preventDefault();
            $(this).passwordChecker();
        })
});
