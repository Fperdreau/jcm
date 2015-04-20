/**
 * Created by Florian on 30/03/2015.
 */

function getPlugins(page, callback) {
    jQuery.ajax({
        url: 'php/form.php',
        type: 'POST',
        data: {
            get_plugins: true,
            page: page
        },
        async: true,
        success: function(data) {
            var json = jQuery.parseJSON(data);
            callback(page, json);
        }
    });
}

function showplugins(page, result) {
    var key;
    for (key in result) {
        var plugin = result[key];
        if (plugin.page == page) {
            console.log(plugin.display);
            $(".plugins")
                .fadeOut(200)
                .append(plugin.display)
                .fadeIn(200);
        }
    }
}

$(document).ready(function() {

    $('.mainbody')

        .on('blur','.plugin_setting', function(e){
            e.preventDefault();
            var input = $(this);
            var option = $(this).attr('data-option');
            var plugin = $(this).attr('data-plugin');
            var value = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    mod_plugins: true,
                    plugin: plugin,
                    option: option,
                    value: value
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json === true) {
                        console.log(json);
                        input
                            .addClass('valid_input');
                        setTimeout(function(){
                            input.removeClass('valid_input');
                        }, 500)
                    } else {
                        input
                            .addClass('wrong_input');
                        setTimeout(function(){
                            input.removeClass('wrong_input');
                        }, 500)
                    }
                }
            });
        })

        .on('change','.plugin_status',function(e) {
            e.preventDefault();
            var input = $(this);
            var plugin = $(this).attr('data-plugin');
            var value = $(this).val();
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    plugin_status: true,
                    plugin: plugin,
                    status: value
                },
                async: true,
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if (json === true) {
                        input
                            .addClass('valid_input');
                        setTimeout(function(){
                            input.removeClass('valid_input');
                        }, 500)
                    } else {
                        input
                            .addClass('wrong_input');
                        setTimeout(function(){
                            input.removeClass('wrong_input');
                        }, 500)
                    }
                }
            });
        })

        .on('click','.install_plugin',function(e) {
            e.preventDefault();
            var plugin = $(this).attr('data-plugin');
            var op = $(this).attr('data-op');
            jQuery.ajax({
                url: 'php/form.php',
                type: 'POST',
                data: {
                    install_plugin: true,
                    plugin: plugin,
                    op: op
                },
                async: true,
                beforeSend: function() {
                    $(this).html('<div style="text-align: center; padding: 0; margin: 0;"><img src="images/36.gif" width="70%"></div>');
                },
                complete: function() {
                    $(this).html('Download a PDF');
                },
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    var result = (op=='install') ? 'installed':'uninstalled';
                    if (json === true) {
                        var newcontent = (op=='install') ? 'Uninstall':'Install';
                        $(this).html(newcontent);
                        showfeedback("<p id='success'>Plugin successfully "+result+"</p>");
                    } else {
                        showfeedback("<p id='warning'>Oops, something has gone wrong</p>");

                    }
                }
            });
        });
});