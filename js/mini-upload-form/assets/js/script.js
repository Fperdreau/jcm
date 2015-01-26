$(function(){

    $(".mainbody")

        .on('click','#drop a',function(){
            // Simulate a click on the file input button
            // to show the file browser dialog
            $(this).parent().find('input').click();
        })

        // Initialize the jQuery File Upload plugin
        .on("mouseover","#upload",function(){
            var ul = $('#upload ul');

            $("#upload").fileupload({

                // This element will accept file drag/drop uploading
                dropZone: $('#drop'),

                // This function is called when a file is added to the queue;
                // either via the browse button, or via drag/drop:
                add: function (e, data) {

                    var tpl = $('<li class="working"><input type="text" value="0" data-width="28" data-height="28"'+
                        ' data-fgColor="#BE4141" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span></li>');

                    // Append the file name and file size
                    tpl.find('p').text(data.files[0].name)
                                 .append('<i>' + formatFileSize(data.files[0].size) + '</i>');

                    // Add the HTML to the UL element
                    data.context = tpl.appendTo(ul);

                    // Initialize the knob plugin
                    tpl.find('input').knob();

                    // Listen for clicks on the cancel icon
                    tpl.find('span').click(function(){

                        if(tpl.hasClass('working')){
                            jqXHR.abort();
                        }

                        tpl.fadeOut(function(){
                            tpl.remove();
                        });

                    });

                    // Automatically upload the file once it is added to the queue
                    var jqXHR = data.submit().success(function(result, textStatus, jqXHR) {
                        var json = JSON.parse(result);
                        var status = json['status'];
                        if (status == 'no_file') {
                            tpl.find('p').html('No file to upload');
                        } else if (status == 'failed') {
                            tpl.find('p').html('Upload failed');
                        } else {
                            tpl.find('p').html('<a href="uploads/'+status+'" target="_blank">'+status+'</a>')
                                .append('<i>' + formatFileSize(data.files[0].size) + '</i>');
                            $('#submit_form').append('<input type="hidden" name="link" id="link" value="'+status+'" />');
                        }
                    });
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

                fail:function(e, data){
                    // Something has gone wrong!
                    data.context.addClass('error');
                }

            });
        })

        // Prevent the default action when a file is dropped on the window
        .on('drop dragover', function (e) {
            e.preventDefault();
        });

    // Helper function that formats the file sizes
    function formatFileSize(bytes) {
        if (typeof bytes !== 'number') {
            return '';
        }

        if (bytes >= 1000000000) {
            return (bytes / 1000000000).toFixed(2) + ' GB';
        }

        if (bytes >= 1000000) {
            return (bytes / 1000000).toFixed(2) + ' MB';
        }

        return (bytes / 1000).toFixed(2) + ' KB';
    }

});