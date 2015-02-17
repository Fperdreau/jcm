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
