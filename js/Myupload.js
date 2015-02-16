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
  }

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

      beforeSend: function(response){
          // before send do some func if u want
          $("#response").html(response);
      },

      progress: function(e, data){
          // Calculate the completion percentage of the upload
          var progress = parseInt(data.loaded / data.total * 100, 10);

          // Update the hidden input field and trigger a change
          // so that the jQuery knob plugin knows to update the dial
          data.context.find('input').val(progress).change();

          if(progress == 100){
              data.context.removeClass('working');
          }
      },

      success: function(response){
          result = jQuery.parseJSON(response);
          $('.upl_container').find('#upl_errors').hide();
          var status = result.status;
          var error = result['error'];
          if (error === true) {
            var name = result.name;
            $('#submit_form').append('<input type="hidden" class="upl_link" id="'+name+'" value="'+status+'" />');
            $('#upl_filelist').append("<div id='upl_info' class='"+name+"'><div class='upl_name' id='"+status+"'>"+status+"</div><div class='del_upl' id='"+status+"' data-upl='"+name+"'><img src='images/delete.png' style='margin: auto; width: 15px; height: 15px;' alt='delete'></div></div>");
          } else {
            $('.upl_container').find('#upl_errors').html(error).show();
          }
      },

      complete: function(response){
          // do some func after complete if u want
      },

      error: function(response){
          // here is what u want
          alert ("Error: " + response.statusText);
      },

    });
  }

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
    })

    .on('click','.upl_btn', function() {
      $('#upl_input').click();
    })

    .on('change','#upl_input',function(e) {
        e.preventDefault();
        var fileInput = $('#upl_input')[0];
        for(var i = 0; i < fileInput.files.length; ++i){
            var data = new FormData();
            data.append('file[]',fileInput.files[i]);
            processupl(data);
        }
    });
});

