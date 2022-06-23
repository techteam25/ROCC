var statuses = [0, 0, 0, 0, 0, 0, 0];
var notes = ['', '', '', '', '', '', ''];
var messages = ['', '', '', '', '', '', ''];
var isRequested = [false, false, false, false, false, false, false];
var quill;

$(document).ready(function(){
    quill = new Quill('#slide-notes-editor', {
        modules: {
          toolbar: [
            [ {size: []} ], ['bold', 'italic', 'underline'], 
            [ {color: []}, {background: []} ], 
            ['clean']
          ]
        },
        theme: 'snow'
    });
    var wsQuill = new Quill('#ws-notes-editor', {
        modules: {
          toolbar: [
            [ {font: []}, {size: []} ],
            ['bold', 'italic', 'underline'],
            [ {color: []}, {background: []} ],
            [ {list: 'bullet'} ], /*, {list: 'ordered'}, ], */
            [ 'clean' ],
          ]
        },
        theme: 'snow'
    });

    // $('[data-toggle="tooltip"]').tooltip();

    $(document).find("*").attr('draggable', false);

    var currProj = $(document).find('.current-project');
    currProj.css('background-color', '#ccc');
    currProj.next().css('display', 'block');

    $('#reference-search').val($('#verse-reference').html());
});

/**
 *  Functions to change current slide displayed in ROCC 
 */

$(document).on("click",".slide-preview", function() {
    changeActiveSlide($(this));
});

$(document).on('click', '#previous', function() {    
    var newActiveSlide = $('.active-slide').prev();
    if (newActiveSlide.length != 0) {
        changeActiveSlide(newActiveSlide);
    }
});

$(document).on('click', '#next', function() {
    var newActiveSlide = $('.active-slide').next();
    if (newActiveSlide.length != 0) {
        changeActiveSlide(newActiveSlide);
    }
});

/**
 *  Stories menu functions 
 */

$(document).on('click', '#stories-menu-button', function() {
    $('#stories-menu').toggle();
    $('#content-overlay').toggle();

    var display = $('#stories-menu').css('display');
    if (display == 'block') {
        $('#stories-menu-button').css('background-color', 'rgb(25, 111, 182)');
    } else {
        $('#stories-menu-button').css('background-color', '');
    }
});

$(document).on('click', '.language-project', function() {
    var list = $(this).next();
    if (list.css('display') == 'none') {
        $(this).css('background-color', '#ccc');
    } else {
        $(this).css('background-color', '');
    }
    
    list.slideToggle(200);    
});

$(document).on('click', '.menu-close', function() {
    $('#stories-menu').toggle();
    $('#content-overlay').toggle();

    $('#stories-menu-button').css('background-color', '');
});

/**
 * Slide logs functions 
 */

$(document).on('click', '#logs-toggle-button', function() {
    var isDisplayed =  $('#slide-logs').hasClass('slide-logs-visible');

    if (!isDisplayed) {
        $('#slide-logs').addClass('slide-logs-visible');
        changeLogDisplay('&#171', '250px', '207px', '5px 5px 2px 2px rgba(77,76,77,0.40)', '-1px 5px 2px 2px rgba(77,76,77,0.40)');
    } else {
        $('#slide-logs').removeClass('slide-logs-visible');
        changeLogDisplay('&#187', '0', '-43px');
    }
});

function changeLogDisplay(sym, lval1, lval2, shadow1 = '', shadow2 = '') {
    $('#logs-toggle-button').html(sym);
    $('#logs-toggle-button').animate({left: lval1}, 300);
    $('#slide-logs').animate({left: lval2}, 300);
    $('#slide-logs').css('box-shadow', shadow1);
    $('#community-option').css('box-shadow', shadow2);
}

$(document).on('change', '.log-display', function() {
    var element = this.id;
    var logClass = '.' + element.substring(0, element.indexOf('-')) + '-logs';

    if (this.checked) {
        $(logClass).css('display', 'block');
    } else {
        $(logClass).css('display', 'none');
    }
});

/**
 * Slide status change function
 */

$(document).on('click', '.status-button', function() {
    $('.active-status i').css('display', 'none');
    $('.active-status').removeClass('active-status');

    $(this).addClass('active-status');
    $('.active-status i').css('display', 'block');

    var element = this.id;
    var color = element.substring(0, element.indexOf('-'));
    $(".active-slide i").css('color', color);

    if (color == 'red') {
        statuses[getSlideNum()] = -1;
    } else if (color == 'green') {
        statuses[getSlideNum()] = 1;
    } else {
        statuses[getSlideNum()] = 0;
    }
});

/**
 * Message area functions
 */

$(document).on('keydown', '#new-message textarea', function(e) {
    if (e.which == 13 && !e.shiftKey) {
        e.preventDefault();
        $('#send-button').click();
    }
});

$(document).on('click', '#send-button', function() {
    var message = $('#new-message textarea').val();
    if (message !== '') {
        $('#message-history').append('<div class="message-span"> \
            <div class="message-box sent-message">' + message + '</div> \
            </div>'); 
    }

    $('#message-history').scrollTop($('#message-history').height());
    $('#new-message textarea').val('');
});

$(document).on('change', '#request-check', function() {
    if (this.checked) {
        $(this).attr('disabled', true);
        $('#message-control label').html('Transcript Requested');
        $('label').css('color', 'rgba(77,76,77,0.75)');
    }
});

/**
 * Other helper functions for prototype
 */

function changeActiveSlide(newActiveSlide) {
    notes[getSlideNum()] = quill.getContents();
    messages[getSlideNum()] = $('#message-history').html();
    $('.active-slide').removeClass('active-slide');
    newActiveSlide.addClass('active-slide');
    var img = newActiveSlide.find("img");
    var src = img.attr('src');
    $('#slide-picture').find("img").attr('src', src);
    checkSource(src);
    updateSlideNotes();
    updateSlideMessages();
    updateSlideStatus();
}

function checkSource(src) {
    if (src == '0.jpg') {
        $('#slide-text').html('<p>Widow\'s Gift</p><p id="verse-reference">Mark 12:41-44</p><p>Jesus honored [praised] a poor widow woman for her generous gift to God.</p>');
    } else if (src == '1.jpg') {
        $('#slide-text').html('<p id="verse-reference">Mark 12:41</p><p>One day, Jesus went into the inner courtyard of the Big Worshipping Place [Temple] with his close friends [disciples].</p><p>They sat down near the container [offering box] where the people were putting their money that they were giving to God.</p>');
    } else if (src == '2.jpg') {
        $('#slide-text').html('<p id="verse-reference">Mark 12:41</p><p>There were many rich people who gave large amounts of money.</p>');
    } else if (src == '3.jpg') {
        $('#slide-text').html('<p id="verse-reference">Mark 12:42</p><p>Jesus saw a poor widow woman as she came to that place. Jesus indicated that his close friends [disciples] should watch that woman. She put into the offering container two very small coins [like pennies with little value that would not buy much food].</p>');
    } else if (src == '4.jpg') {
        $('#slide-text').html('<p id="verse-reference">Mark 12:43-44</p><p>Jesus said to his friends [disciples], “Learn now!  God has accepted that woman’s money [gift] with very much joy!  Some people gave much money, but they still had much money left. This woman is very poor. Yet, she has given all the money that she had [which she might have used for food today and tomorrow]!”</p>');
    } else if (src == '5.jpg') {
        $('#slide-text').html('<p id="verse-reference">Mark 12:43</p><p>“God considers this woman’s gift to be worth very much more money [more value] than all the other people’s gifts added together!”</p>');
    } else if (src == '6.jpg') {
        $('#slide-text').html('<p>Widow\'s Gift</p><p>Mark 12:41-44</p>');
    }

    $('#reference-search').val($('#verse-reference').html());
    // Click search button
}

function getSlideNum() {
    var slideNumHtml = $('.active-slide .slide-num').html();
    var num = slideNumHtml.substring(slideNumHtml.indexOf("</p>") + 4, slideNumHtml.indexOf("<i ")); //slideNumHtml[slideNumHtml.length - 1] - 1;
    num = parseInt(num.replace(/\s+/g, ''));
    
    return num;
}

function updateSlideStatus() {
    var status = statuses[getSlideNum()];

    if (status == -1) {
        $("#red-status").click();
    } else if (status == 0) {
        $("#yellow-status").click();
    } else if (status == 1) {
        $("#green-status").click();
    }
}

function updateSlideNotes() {
    quill.setContents(notes[getSlideNum()]);
}

function updateSlideMessages() {
    var messagesHtml = messages[getSlideNum()];
    $('#message-history').html(messagesHtml);
}