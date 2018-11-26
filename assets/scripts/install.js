/**
 * Get URL parameters
 */
function getParams() {
    var url = window.location.href;
    var splitted = url.split("?");
    if(splitted.length === 1) {
        return {};
    }
    var paramList = decodeURIComponent(splitted[1]).split("&");
    var params = {};
    for(var i = 0; i < paramList.length; i++) {
        var paramTuple = paramList[i].split("=");
        params[paramTuple[0]] = paramTuple[1];
    }
    return params;
}

var step = 0;
var next_step = 1;

/**
 * Get view
 * @param step_to_load: view to load
 * @param op: update or make new installation
 */
function getpagecontent(step_to_load, op) {
    var step = step_to_load;
    var stateObj = { page: 'install' };
    var div = $('main');

    var callback = function(result) {
        history.pushState(stateObj, 'install', "install.php?step=" + result.step + "&op=" + result.op);
        pageTransition(result);
    };
    var data = {get_page_content: step, op: op};
    processAjax(div,data,callback,'install.php');
}

/**
 * Handle page transition
 * 
 * @param {*} content 
 */
function pageTransition(content) {
    var container = $('#hidden_container');
    var current_content = container.find('#current_content');

    if (container.find('#next_content').length === 0) {
        container.append('<div id="next_content"></div>');
        renderSection(current_content, content);
        return true;
    }

    var next_content = container.find('#next_content');
    renderSection(next_content, content);

    current_content.animate({'margin-left': '-100%', 'opacity': 0}, 1000, function() {
        var next_content = $(this).siblings('#next_content');
        next_content.attr('id', 'current_content');
        next_content.after('<div id="next_content"></div>');
        $(this).remove();

    });

}

function renderSection(section, content) {
    var defaultHtml = '<div class="box"><div id="section_title"></div><div id="section_content"></div></div>';
    section.html(defaultHtml);
    section.find('#section_content').html(content.content);
    section.find('#section_title').html(content.title);
}

/**
 * Show loading animation
 */
function loadingDiv(el) {
    el.css('position', 'relative');
    if (el.find('.loadingDiv').length === 0) {
        el.append("<div class='loadingDiv'></div>");
    }
    el.find('.loadingDiv').css('position', 'absolute').fadeIn();
}

/**
 * Remove loading animation at the end of an AJAX request
 * @param el: DOM element in which we show the animation
 */
function removeLoading(el) {
    el.fadeIn(200);
    el.find('.loadingDiv')
        .fadeOut(1000)
        .remove();
}

/**
 * Render progression bar
 */
function progressbar(el, percent, msg) {
    el.css('position', 'relative');
    el.find(".progress_layer").remove();
    el.append('<div class="progress_layer"></div>');
    var layer = el.find('.progress_layer');
    if (layer.find('.progressText_container').length === 0) {
        layer.append('<div class="progressText_container">' +
            '<div class="text"></div>' +
            '<div class="progressBar_container"><div class="progressBar"></div>' +
            '<div class="progressBar_loading"></div></div>');
    }
    var TextContainer = el.find('.text');
    TextContainer.html(msg);

    var progressBar = el.find('.progressBar_container');
    var width = progressBar.width();
    progressBar.children('.progressBar').css({'width': percent * width + 'px'});
}

/**
 * Remove progression bar
 * @param {*} el 
 */
function remove_progressbar(el) {
    el.find(".progress_layer").remove();
}

/**
 * Update operation
 */
function modOperation(data,operation) {
    var i;
    // Find and replace `content` if there
    for (i = 0; i < data.length; ++i) {
        if (data[i].name === "operation") {
            data[i].value = operation;
            break;
        }
    }
    return data;
}

/**
 * Go to next installation step
 **/
function gonext() {
    var op = $('.section_content#operation').data('action');
    var step = $('.section_content#operation').data('next');
    getpagecontent(step, op);
    return true;
}

/**
 * Application installation
 * @param input
 * @returns {boolean}
 */
function process(input) {
    var form = input.length > 0 ? $(input[0].form) : $();
    var operation = form.find('input[name="operation"]').val();
    var op = form.find('input[name="op"]').val();
    var data = form.serializeArray();
    var operationDiv = $('#operation');

    // Check form validity
    if (!checkform(form)) return false;

    var queue = [
        {url: 'php/router.php?controller=Db&action=testdb', operation: 'db_info', data: data, text: 'Connecting to database'},
        {url: 'php/router.php?controller=Config&action=createConfig', operation: 'do_conf', data: data, text: 'Creating configuration file'},
        {url: 'php/router.php?controller=Backup&action=backupDb', operation: 'backup', data: data, text: 'Backup files and database'},
        {url: 'php/router.php?controller=App&action=install', operation: 'install_db', data: data, text: 'Installing application'},
        {url: 'php/router.php?controller=Patcher&action=patching', operation: 'apply_patch', data: data, text: 'Applying patch'},
        {url: 'php/router.php?controller=Session&action=checkDb', operation: 'checkDb', data: data, text: 'Checking database integrity'}
    ];

    var lastAction = function() {
        progressbar(operationDiv, 1, 'Installation complete');
        setTimeout(function() {
            remove_progressbar(operationDiv);
            gonext();
            return true;
        }, 1000);
    };
    recursive_ajax(queue, operationDiv, queue.length, lastAction);
    return true;
}

/**
 * Run installation steps using recursive call
 * @param queue
 * @param el
 * @param init_queue_length
 * @param lastAction: function to execute once the queue is empty
 */
function recursive_ajax(queue, el, init_queue_length, lastAction) {
    var percent = 1 - (queue.length / init_queue_length);
    if (queue.length > 0) {
        var dataToProcess = modOperation(queue[0].data, queue[0].operation);
        jQuery.ajax({
            url: queue[0].url,
            type: 'post',
            data: dataToProcess,
            async: true,
            timeout: 20000,
            beforeSend: function() {
                progressbar(el, percent, queue[0].text);
            },
            success: function(data) {
                var result = jQuery.parseJSON(data);
                if (result.status) {
                    progressbar(el, percent, result.msg);
                    queue.shift();
                    setTimeout(function() {
                        recursive_ajax(queue, el, init_queue_length, lastAction);
                    }, 1000);
                } else {
                    progressbar(el, percent, result.msg);
                    setTimeout(function() {
                        remove_progressbar(el);
                        return true;
                    }, 3000);
                    return false;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                progressbar(el, percent, textStatus);
                setTimeout(function() {
                    remove_progressbar(el);
                    return true;
                }, 3000);

            }
        });
    } else {
        if (lastAction !== undefined) {
            lastAction();
        }
    }
}

// Has the page been loaded already
var loaded = false;

/**
 * Get action and step values from URL for the first load
 * @return void
 */
function first_load() {
    if (!loaded) {
        var params = getParams();
        getpagecontent(params.step, params.op);
        loaded = true;
    }
}

/**
 * Test email settings
 *
 * @param el
 */
function testEmailSettings(el) {
    var form = el.length > 0 ? $(el[0].form) : $();
    var data = form.serializeArray();
    processAjax(form, data, false, 'php/router.php?controller=MailManager&action=sendTestEmail');
}


$(function () {

    // Get page content for the first load only
    first_load();

    $('body')

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
            Installation/Update
            %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // Go to next installation step
        .on('click', '.start', function(e) {
            e.preventDefault();
            var op = $(this).attr('data-op');
            $('.section_content#operation').data('action', op);
            gonext(op);
        })

        // Go to next installation step
        .on('click', '.finish', function(e) {
            e.preventDefault();
            window.location = "index.php";
        })

        .on('click', "input[type='submit']", function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!$(this).hasClass('process_form') && !$(this).hasClass('test_email_settings')) {
                process($(this));
            } else {
                return false;
            }
        })

        .on('click',".process_form",function(e) {
            e.preventDefault();
            e.stopPropagation();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            if (!checkform(form)) {return false;}
            var callback = function(result) {
                if (result.status === true) {
                    gonext();
                }
            };
            processForm(form,callback,form.attr('action'));
        })

        // Test email host settings
        .on('click', '.test_email_settings', function(e) {
            e.preventDefault();
            testEmailSettings($(this));
        })

        // Final step: Create admin account
        .on('click','.admin_creation',function(e) {
            e.preventDefault();
            var form = $(this).length > 0 ? $($(this)[0].form) : $();
            var op = form.find('input[name="op"]').val();
            if (!checkform(form)) {return false;}
            var callback = function(result) {
                if (result.status === true) {
                    getpagecontent(5,op);
                }
            };
            processForm(form,callback,'install.php');
        });
});