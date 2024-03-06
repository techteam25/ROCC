
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
    host = externalWebsocketHost;
    hostpre = host.slice(0, 9);
    if (hostpre === "10.10.10.")
    {
	service = 'ws';
    }
    else
    {
	service = 'wss';
    }
    let conn = new WebSocket(`${service}://${host}:${externalWebsocketPort}/consultant/${projectId}/${storyId}`);
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
    conn.onclose = () => {
	console.log("closed connection");
	connection = connect(); // Reconnect
    }
    return conn;
};

function changeSlide(slideNumber, oldSlideNumber) {
    if (slideNumber > -1 && slideNumber < slideCount) {
        
        console.log(oldSlideNumber);
        saveNotes(oldSlideNumber);        

        currentSlide = slideNumber;
        let fileDisplayArea = document.getElementById('mainText');
        let storyRoot = `Files/Projects/${projectId}/${storyId}`;
        document.getElementById("mainAudio").src = `${storyRoot}/${slideNumber}.m4a`;
//        fileDisplayArea.setAttribute('data', `${storyRoot}/${slideNumber}.txt`);


	//The slide name in the slide. Also check for Song slide
	if(slideNumber == songSlideNumber){
        document.getElementById("status").innerHTML = "Song Slide";
	}else{
        document.getElementById("status").innerHTML = "Slide " + currentSlide;
	}

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
                lastNotes = data;
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

	    //Set the border of the Song slide to normal if songSlideNumber is not deactivated by setting it = -1
	    if(!(songSlideNumber == -1)){
        document.getElementById("thumbnail Song").style.backgroundColor = "#25274d";
	    }

	    //checking if this is the song slide
	    if(slideNumber == songSlideNumber){
        	document.getElementById(`msgImgSong`).style.display = 'none';
	    }else{
        document.getElementById(`msgImg${slideNumber}`).style.display = 'none';
	    }

        //Set border around selected thumbnail... And checking for the Song slide
	    if(slideNumber == songSlideNumber){
        	document.getElementById("thumbnail Song").style.backgroundColor = "#fff";
	    }else{
        document.getElementById("thumbnail " + slideNumber).style.backgroundColor = "#fff";
	    }
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
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function saveNotes(slideNumber) {
    let myEditor = document.querySelector('#editor-container');
    let notes = myEditor.children[0].innerHTML;
    if (notes != lastNotes)
    {
      if (lastNotes != "") // Don't save before field is first set
      {
        console.log("in saveNotes: " + slideNumber + " " + notes + "!" + lastNotes);
        $.ajax({
          data: "notes=" + notes +
          "&slideNumber=" + slideNumber +
          "&storyId=" + storyId,
          url: "API/submitNote.php",
          type: "POST",
          success: function () { 
          }
        });
      }
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
      console.log(notes);
      console.log(storyId);      
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
        tabcontent[i].classList.remove('active')
    }
    tablinks = document.getElementsByClassName("tablinks");
    for(i=0; i<tablinks.length; i++){
        tablinks[i].className = tablinks[i].className.replace("active", "");
    }
    document.getElementById(contentName).style.display = "block";
    document.getElementById(contentName).classList.add('active');
    evt.currentTarget.className += " active";
}

//changed so as to not change slide when user is editing text in one of the text boxes. May need to add more exceptions when more stuff is added!
$(document).keydown(function(e) {
        var focused = document.activeElement.parentNode;
    if(!((focused.id === "editor-container2") || (focused.id === "editor-container") || ($(e.target).is('input,text')))){
    
        if(e.which === 37){
                changeSlide(currentSlide - 1, currentSlide);
            //scroll up
            document.getElementById("thumbnail_text_" + currentSlide).scrollIntoView({block: "nearest", inline: "nearest"});
    }
            else if(e.which === 39){
                changeSlide(currentSlide + 1, currentSlide);
            //scroll down
            if(currentSlide == songSlideNumber){
                document.getElementById("thumbnail Song").scrollIntoView({block: "nearest", inline: "nearest"});
            }else{
                document.getElementById("thumbnail " + currentSlide).scrollIntoView({block: "nearest", inline: "nearest"});
            }
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

const searchInput = document.getElementById("termListSearchBox");
// Bind search input change event to filter function
searchInput.addEventListener("input", (event) => {
    const searchTerm = event.target.value;
    filterList(searchTerm);
});

function filterList(searchTerm) {
    const searchTermLowerCase = searchTerm.toLowerCase();
    var searchItermFound = false;
    const listContainer = document.getElementById("termList");
    listContainer.querySelectorAll("button").forEach(listItem => {
        // ignore no term found message item
        if (listItem.id === "noTermFound") {
            return;
        }
        const itemText = listItem.textContent.toLowerCase();
        const isMatch = itemText.includes(searchTermLowerCase);
        listItem.style.display = isMatch ? "block" : "none";

        if (isMatch) {
            searchItermFound = true;
        }
    });

    // display no term found element if no item is visible
    if (searchItermFound) {
        document.getElementById("noTermFound").classList.add("hide");
    } else {
        document.getElementById("noTermFound").classList.remove("hide");
    }
}


function showTermDetails(evt, term) {
    evt.preventDefault();
    // show WordLinks tab if not visible
    if (!document.getElementById('WordLinks').classList.contains('active')) {
        openTab(event, 'WordLinks');
    }

    const decodedTermlink = decodeURIComponent(term.toLowerCase());
    const tl = document.querySelector('#termDetailTemplate');
    // clone template and append available data
    const template = tl.cloneNode(true);
    const headerEl = template.content.querySelector('.termHeader h2');
    headerEl.innerHTML = decodedTermlink;
    template.content.querySelector('.notes').textContent = wordLinkTerms[decodedTermlink].notes;
    template.content.querySelector('.backTranslation').textContent = wordLinkTerms[decodedTermlink].backTranslations;

    const alternateTerms = wordLinkTerms[decodedTermlink].alternateTerms;
    if (alternateTerms.length > 0) {
        alternateTerms.forEach(function(term) {
            if (term.length > 1) {
                template.content.querySelector('.alternateTerms').appendChild(generateTermItem(term));
            }
        });
    }

    const relatedTerms = wordLinkTerms[decodedTermlink].relatedTerms;
    if (relatedTerms.length > 0) {
        relatedTerms.forEach(function(term) {
            if (term.length > 1) {
                template.content.querySelector('.relatedTermsList').appendChild(generateTermItem(term, true))
            }
        });
    } else {
        template.content.querySelector('.relatedTerms').classList.add('hide');
    }

    const otherLanguageExamples = wordLinkTerms[decodedTermlink].otherLanguageExamples;
    if (otherLanguageExamples.length > 0) {
        otherLanguageExamples.forEach(function(term) {
            if (term.length > 1) {
                template.content.querySelector('.otherLanguageExamplesList').appendChild(generateTermItem(term))
            }
        });
    } else {
        template.content.querySelector('.otherLanguageExamples').classList.add('hide');
    }

    document.getElementById("termDetails").replaceChildren(template.content);
    document.querySelector('ul.relatedTermsList').addEventListener("click", function(event) {
        if (event.target.matches('a')) {
            showTermDetails(event, event.target.textContent)
        }
    });

    getWordLinkRecording(decodedTermlink);
    document.getElementById("termList").classList.add("hide");
    document.getElementById("termDetails").classList.remove("hide");
}

function generateTermItem(t, clickable = false) {
    let term = t.trim().toLowerCase();
    const li = document.createElement('li');
    if (clickable && wordLinkTerms.hasOwnProperty(term)) {
        const a = document.createElement('a');
        a.textContent = term;
        li.append(a);
    } else {
        li.textContent = term
    }

    return li;
}

function showTermList(evt) {
    document.getElementById("termDetails").classList.add("hide");
    document.getElementById("termList").classList.remove("hide");
    filterList("");
    searchInput.value = "";
}

function getWordLinkRecording(term) {
       $.ajax({
        url: "API/GetWordLinkRecording.php",
        data: {
            "term": term,
            "PhoneId": projectId,
        },
        type: "POST",
        success: function (data) {
            if (data.Success)
            {
                document.querySelector('p.backTranslation').textContent = data.backTranslation;
                if (data.audioFileLink.length > 0) {
                    document.querySelector('.wordLinkAudio').src = `${data.audioFileLink}`;
                    return
                }
            }

            const noAudioFileMessage = document.createElement('p');
            noAudioFileMessage.classList.add('noAudioFileMessage');
            noAudioFileMessage.textContent = "No recording uploaded";
            document.querySelector('.wordLinkAudio').replaceWith(noAudioFileMessage)
        },
    });
}