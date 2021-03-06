
let localStorageMessages = `messages/${projectId}/${storyId}`;
let messagesStorageString = window.localStorage.getItem(localStorageMessages);
let messages = [];
if (messagesStorageString && messagesStorageString != '') {
    messages = JSON.parse(messagesStorageString);
}

let localStorageApprovals = `approvals/${projectId}/${storyId}`;
let approvalsStorageString = window.localStorage.getItem(localStorageApprovals);
let approvals = [];
if (approvalsStorageString && approvalsStorageString != '') {
    approvals = JSON.parse(approvalsStorageString);
}

let slideUnreadState = [];
let lastNotes = "";
let lastWholeNotes = "";

function parseMessageDateString(dateString) {
    let splitDateString = dateString.split(/[- :]/);
    return new Date(Date.UTC(
        splitDateString[0], 
        splitDateString[1] - 1, 
        splitDateString[2], 
        splitDateString[3], 
        splitDateString[4], 
        splitDateString[5]
    ));
}

let localStorageLastTimeSent = `lastTimeSent/${projectId}/${storyId}`;
let timeSentStorageString = window.localStorage.getItem(localStorageLastTimeSent);
let lastTimeSent = null;
if (timeSentStorageString && timeSentStorageString != '') {
    lastTimeSent = parseMessageDateString(timeSentStorageString);
}

function syncApprovalSwitch(slideNumber) {
    let checkbox = document.getElementById('approveSwitch');
    let checkImg = document.getElementById(`checkImg${slideNumber}`);
    if (approvals[slideNumber]) {
        if (slideNumber === currentSlide) {
            checkbox.checked = true;
        }
        if (checkImg) checkImg.style.display = 'block';
    } else {
        if (slideNumber === currentSlide) {
            checkbox.checked = false;
        }
        if (checkImg) checkImg.style.display = 'none';
    }
}

function connect() {
    let conn = new WebSocket(`wss://${window.location.hostname}:${websocketPort}/consultant/${projectId}/${storyId}`);
    conn.onopen = () => {
        console.log("opened!!!");
        let m = { 'type': 'catchup' };
        if (lastTimeSent !== null) {
            m.since = lastTimeSent;
        }
        console.log(m);
        conn.send(JSON.stringify(m));
    };
    conn.onmessage = (e) => {
        console.log(e.data);
        let message = JSON.parse(e.data);
        let messageTimeSent = parseMessageDateString(message.timeSent);
        if (lastTimeSent === null || messageTimeSent > lastTimeSent) {
            lastTimeSent = messageTimeSent;
            window.localStorage.setItem(localStorageLastTimeSent, message.timeSent);
        }
        if (message.type === 'text') {
            messages.push(message);
            window.localStorage.setItem(localStorageMessages, JSON.stringify(messages));
            if (message.slideNumber == currentSlide) {
                addMessage(message);
            } else if (!slideUnreadState[message.slideNumber]) {
                document.getElementById(`msgImg${message.slideNumber}`).style.display = 'block';
            }
        } else if (message.type === 'approval') {
            let slideNumber = message.slideNumber;
            approvals[slideNumber] = message.approvalStatus;
            window.localStorage.setItem(localStorageApprovals, JSON.stringify(approvals));
            syncApprovalSwitch(slideNumber);
        }
    };
    return conn;
};

function changeSlide(slideNumber, oldSlideNumber) {
    if (slideNumber > -1 && slideNumber < slideCount) {
        
        console.log(oldSlideNumber);
        console.log("in changeSlide");

        currentSlide = slideNumber;
        let fileDisplayArea = document.getElementById('mainText');
        let storyRoot = `Files/Projects/${projectId}/${storyId}`;
        document.getElementById("mainAudio").src = `${storyRoot}/${slideNumber}.m4a`;
//        fileDisplayArea.setAttribute('data', `${storyRoot}/${slideNumber}.txt`);
        document.getElementById("status").innerHTML = "Slide " + currentSlide;

        //displaying transcript files from directory.

	setProperties(slideNumber);
        //display approval from DB
        let checkbox = document.getElementById('approveSwitch');
        if (approvals[slideNumber]) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }

        //display slide-by-slide notes from DB

        $.ajax({
            data: "slideNumber=" + slideNumber + "&storyId=" + storyId,
            url: "API/getNote.php",
            type: "POST",
            success: function (data) {
                editor = document.getElementById('editor-container');
                editor.firstChild.innerHTML = data;
            }
        });

        //display WholeStory notes from DB

        $.ajax({
            data: "storyId=" + storyId,
            url: "API/getWholeStoryNote.php",
            type: "POST",
            success: function (data) {
                editor = document.getElementById('editor-container2');
                editor.firstChild.innerHTML = data;
            }
        });

        document.getElementById("audioPlayer").load();

        let messagesContainer = document.getElementById("messagesContainer");
        messagesContainer.innerHTML = "";
        for (let message of messages) {
            if (message.slideNumber == slideNumber) {
                addMessage(message);
            }
        }

        saveNotes(oldSlideNumber);

        //Clear isUnread flag for messages
        $.ajax({
            data: "storyId=" + storyId + "&slideNumber=" + slideNumber,
            url: "API/readMessage.php",
            type: "POST",
            success: function (data) {
            }
        });

        //Set border to normal for all other thumbnails
        for(i = 0; i < slideCount; i++){
            let thumbnail = document.getElementById("thumbnail " + i);
            if (thumbnail) {
                thumbnail.style.backgroundColor = "#25274d";
            }
        }

        let msg = document.getElementById(`msgImg${slideNumber}`)
	if (msg != null)
	    msg.style.display = 'none';
        //Set border around selected thumbnail
        let thumb = document.getElementById("thumbnail " + slideNumber);
	if (thumb != null)
            thumb.style.backgroundColor = "#fff";
    }
}

function addMessage(message) {
    let messagesContainer = document.getElementById("messagesContainer");
    let messageClass = 'fieldMessage';
    if (message.isConsultant === true) {
        messageClass = 'consultantMessage';
    } else if (message.isTranscript === true) {
        messageClass = 'transcriptMessage';
    }
    messagesContainer.innerHTML += `<div class='${messageClass}'>${message.text}</div>`;
}

function saveNotes(slideNumber) {
    let myEditor = document.querySelector('#editor-container');
    let notes = myEditor.children[0].innerHTML;
    if (notes != lastNotes)
    {
      console.log("in saveNotes");
      $.ajax({
        data: "notes=" + notes +
        "&slideNumber=" + slideNumber +
        "&storyId=" + storyId,
        url: "/API/submitNote.php",
        data: "notes=" + notes + "&slideNumber=" + slideNumber + "&storyId=" + storyId,
        url: "API/submitNote.php",
        type: "POST",
        success: function (data) { 
            console.log(notes);
        }
      });
      lastNotes = notes;
    }
}

function approveSwitchChanged(slideNumber, checkbox, event) {
    connection.send(JSON.stringify({
        'type': 'approval',
        'slideNumber': currentSlide,
        'approvalStatus': checkbox.checked
    }));
    event.preventDefault();
    return false;
}

function saveWholeNotes() {
    let myEditor = document.querySelector('#editor-container2');
    let notes = myEditor.children[0].innerHTML;
    //document.getElementById("textField").value;
    if (notes != lastWholeNotes)
    {
      console.log("in saveWholeNotes");
      $.ajax({
        data: "notes=" + notes +
        "&storyId=" + storyId,
        url: "API/submitWholeStoryNote.php",
        type: "POST",
        success: function () {
        }
      });
      lastWholeNotes = notes;
    }
}

function sendMessageInputKeyPress(e) {
    if (e.keyCode === 13) {
        sendMessage();
    }
}

function sendMessage() {
    const sendMessageInput = document.getElementById("sendMessageInput");
    if (sendMessageInput.value !== '') {
        connection.send(JSON.stringify({
            'type': 'text',
            'storyId': storyId,
            'slideNumber': currentSlide,
            'text': sendMessageInput.value,
        }));
        sendMessageInput.value = '';
    }
}

new Quill('#editor-container', {
    modules: {
	    toolbar: [
		    ['bold', 'italic'],
		    [{list: 'ordered'}, {list: 'bullet'}],
		    [{ 'background': [] }, { 'color': [] }],          // dropdown with defaults from theme
		    [{ 'align': [] }]
        ]
    },
    placeholder: 'Enter Notes',
    theme: 'snow'
});

new Quill('#editor-container2', {
    modules: {
        toolbar: [
		['bold', 'italic'],
		[{list: 'ordered'}, {list: 'bullet'}],
		[{ 'background': [] }, { 'color': [] }],          // dropdown with defaults from theme
		[{ 'align': [] }]
	]
    },
    placeholder: 'Enter Notes',
    theme: 'snow'
});

//for tabs in lower right div
function openTab(evt, contentName){
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for(i=0; i<tabcontent.length; i++){
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for(i=0; i<tablinks.length; i++){
        tablinks[i].className = tablinks[i].className.replace("active", "");
    }
    document.getElementById(contentName).style.display = "block";
    evt.currentTarget.className += " active";
}

//changed so as to not change slide when user is using one of the text boxes. May need to add more exception when more stuff is added!
$(document).keydown(function(e) {
    	var focused = document.activeElement.parentNode;
	if(!((focused.id === "editor-container2") || (focused.id === "editor-container") || ($(e.target).is('input,text')))){
    
		if(e.which === 37){
        changeSlide(currentSlide - 1, currentSlide);
    }
    		else if(e.which === 39){
        changeSlide(currentSlide + 1, currentSlide);
	}
    }
});

//for global hot key to play/pause audio
$(document).keypress(function(e) {

    var focused = document.activeElement.parentNode;
    if(focused.id === "editor-container2"){
        var e = e || window.event;
        var audio = document.getElementById("wholeAudio");
        if(e.shiftKey && e.which == 32){
            if(audio.paused){
                audio.play();
            }
            else{
                audio.pause();

            }
            e.preventDefault();
        }

    }
    else if(focused.id === "editor-container"){
        var e = e || window.event;
        var audio = document.getElementById("audioPlayer");
        if(e.shiftKey && e.which == 32){
            if(audio.paused){
                audio.play();
            }
            else{
                audio.pause();

            }
            e.preventDefault();
        }
    }
});

changeSlide(0, 0);

setInterval(function() {
    saveNotes(currentSlide)
    saveWholeNotes()
    //console.log("auto save");
}, 1000);

let connection = connect();

for (let i in approvals) {
    syncApprovalSwitch(i);
}
