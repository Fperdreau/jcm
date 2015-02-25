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

  // Get file information on drop
  var getdrop = function (e) {
    var dt = e.dataTransfer || (e.originalEvent && e.originalEvent.dataTransfer);

        var files = e.target.files || (dt && dt.files);
        if (files) {
            var nbfiles = files.length;
            for(var i = 0; i < nbfiles; ++i){
              var data = new FormData();
              data.append('file[]',files[i]);
              processupl(data);
            }
        }
  };

  // Uploading process
  var processupl = function (data) {
    jQuery.ajax({
      type:'POST',
      method:'POST',
      url:'php/upload.php',
      headers:{'Cache-Control':'no-cache'},
      data:data,
      contentType:false,
      processData:false,

      success: function(response){
          result = jQuery.parseJSON(response);
          $('.upl_container').find('.upl_errors').hide();
          var status = result.status;
          var error = result.error;
          if (error === true) {
            var name = result.name;
            $('#submit_form').append('<input type="hidden" class="upl_link" id="'+name+'" value="'+status+'" />');
            $('.upl_filelist').append("<div class='upl_info' id='"+name+"'><div class='upl_name' id='"+status+"'>"+status+"</div><div class='del_upl' id='"+status+"' data-upl='"+name+"'><img src='images/delete.png' style='margin: auto; width: 15px; height: 15px;' alt='delete'></div></div>");
          } else {
            $('.upl_container').find('.upl_errors').html(error).show();
          }
      },

      error: function(response){
          $('.upl_container').find('.upl_errors').html(response.statusText).show();
      },

    });
  };

  var progressbar = function(el,value) {
      var size = el.width();
      var linearprogress = value;
      var text = "Progression: "+Math.round(value*100)+"%";

      el
          .show()
          .text(text)
          .css({
              background: "linear-gradient(to right, rgba(200,200,200,.7) "+linearprogress+"%, rgba(200,200,200,0) "+linearprogress+"%)"
          });
  };

  var dragcounter = 0;
  $('.mainbody')

    .on('dragenter','.upl_container', function(e) {
      e.stopPropagation();
      e.preventDefault();
      dragcounter ++;
      $('.upl_container').addClass('dragging');
    })

    .on('dragleave','.upl_container', function(e) {
      e.stopPropagation();
      e.preventDefault();
      dragcounter --;
      if (dragcounter === 0) {
        $('.upl_container').removeClass('dragging');
      }
    })

    .on('dragover','.upl_container',function(e) {
      e.stopPropagation();
      e.preventDefault();
    })

    .on('drop','.upl_container',function(e) {
      e.stopPropagation();
      e.preventDefault();
      getdrop(e);
      $('.upl_container').removeClass('dragging');
    })

    .on('click','.upl_btn', function() {
      $('.upl_input').click();
    })

    .on('change','.upl_input',function(e) {
        e.preventDefault();
        var fileInput = $('.upl_input')[0];
        for(var i = 0; i < fileInput.files.length; ++i){
            var data = new FormData();
            data.append('file[]',fileInput.files[i]);
            processupl(data);
        }
    });
});
