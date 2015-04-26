/*
 Copyright © 2014, Florian Perdreau
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

$(document).ready(function() {
    function showsection(targetID) {
         $('.modal_section').each(function() {
            if ($(this).attr('id') !== targetID) {
                $(this).hide();
            } else {
                $(this).show();
            }
        })
    }

    function closemodal(modalID) {
        $('#overlay').fadeOut(200);
        $('.'+modalID).css({'display':'none'});
    }

    function showmodal(modalID) {
        $('.mainbody').append("<div id='overlay'></div>");
        $('#overlay')
            .css({"display":"block",opacity:0})
            .attr("data-id",modalID)
            .fadeTo(200,0.5);
        $('#'+modalID)
            .css({"display":"block","position":"relative","opacity":0,"z-index":11000,"left":50+"%","margin": "auto"})
            .fadeTo(200,1);
    }

    $('.mainbody')
        .on('click','.tmodal',function(e) {
            e.preventDefault();
            console.log("modal triggered");
            var modalID = $(this).attr('data-mid');
            var targetID = $(this).attr('data-tid');
            $('.'+modalID).show();
            showsection(targetID);
        })

        .on('click','#overlay',function(e) {
            var modalID = $(this).attr('data-id');
            closemodal(modalID);
        });
});
