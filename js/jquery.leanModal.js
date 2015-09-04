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
                closeButton:null};

            var overlay=$("<div id='lean_overlay'></div>");

            $("body").append(overlay);

            options = $.extend(defaults,options);
            return this.each(function(){
                var o=options;
                $(this).click(function(e){
                    var overlayEL = $('#lean_overlay');
                    var modal_id = $(this).data("modal");
                    modal_id = (modal_id === undefined) ? '#modal':modal_id; // Define default DOM target
                    var section = $(this).data('section');

                    overlayEL.click(function(){
                        close_modal(modal_id)
                    });

                    $(o.closeButton).click(function(){
                        close_modal(modal_id)
                    });

                    var max_height=$(window).height();
                    var modal_width=$(modal_id).outerWidth();
                    overlayEL
                        .css({"display":"block",opacity:0})
                        .fadeTo(200,o.overlay);
                    $(modal_id).css({
                        "display":"block",
                        "position":"fixed",
                        "opacity":0,
                        "z-index":11000,
                        "left":50+"%",
                        "margin-left":-(modal_width/2)+"px",
                        "top":o.top+"px"
                    });
                    $(modal_id).fadeTo(200,1);
                    $('.modal_section#'+section).css('max-height',.9*max_height- o.top+'px');
                    show_section(section);
                    e.preventDefault()
                })
            });

        }
    })
})(jQuery);

/**
 * Close modal when clicking outsize the window or on the close button
 * @param modal_id
 */
function close_modal(modal_id){
    $("#lean_overlay").fadeOut(200);
    $(modal_id).css({"display":"none"})
}

/**
 * Show the targeted modal section and hide the others
 * @param sectionid: section id
 * @param modalid: modal container id
 */
function show_section(sectionid,modalid) {
    modalid = (modalid === undefined) ? '#modal':modalid; // Define default DOM target
    $('.modal_section').hide();
    var title = $(modalid+" .modal_section#"+sectionid).data('title');
    $(".popupHeader").text(title);
    $(modalid+' .modal_section').each(function() {
        var thisid = $(this).attr('id');
        if (thisid === sectionid) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

$(document).ready(function() {
    $('body')
        // Bind leanModal to triggers
        .on('mouseover',".leanModal",function(e) {
            e.preventDefault();
            $(this).leanModal({top : 50, overlay : 0.6, closeButton: ".modal_close" });
        })
});
