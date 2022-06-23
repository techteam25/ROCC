//let messages = [];
console.log("got file"); 
/*const connect = () => {
    let conn = new WebSocket(`ws://${window.location.hostname}:3031/consultant`);
    conn.onopen = () => {
        console.log("opened!!!");
    };
    conn.onmessage = (e) => {
        console.log(e.data);
        var message = JSON.parse(e.data);
        messages.push(message);
        addMessage(message);
    };
    return conn;
};

function changeSlide(slideNumber) {
    if (slideNumber > -1 && slideNumber < slideCount) {
        CURR_SLIDE = slideNumber;
        let storyRoot = `Files/Projects/${CURR_PROJ}/${CURR_STORY}`;
        document.getElementById("mainAudio").src = `${storyRoot}/${slideNumber}.wav`;
        document.getElementById("mainText").setAttribute('data', `${storyRoot}/${slideNumber}.txt`);

        //displaying transcript files from directory.
        let fileDisplayArea = document.getElementById('mainText');
        function readTextFile(file)
        {
            let rawFile = new XMLHttpRequest();
            rawFile.open("GET", file, false);
            rawFile.onreadystatechange = function ()
            {
                if (rawFile.readyState === 4)
                {
                    if (rawFile.status === 200 || rawFile.status == 0) {
                        let allText = rawFile.responseText;
                        fileDisplayArea.innerText = allText
                    }
                }
            }
            rawFile.send(null);
        }
        readTextFile(`${storyRoot}/${slideNumber}.txt`);

        //display approval from DB
        let checkbox = document.getElementById('approveSwitch');
        if (approvals[slideNumber]) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }

        //display slide-by-slide notes from DB

        $.ajax({
            data: "slideNumber=" + slideNumber + "&storyId=" + CURR_STORY,
            url: "/API/getNote.php",
            type: "POST",
            success: function (data) {
                editor = document.getElementById('editor-container');
                editor.firstChild.innerHTML = data;
            }
        });

        //display WholeStory notes from DB

        $.ajax({
            data: "storyId=" + CURR_STORY,
            url: "/API/getWholeStoryNote.php",
            type: "POST",
            success: function (data) {
                editor = document.getElementById('editor-container2');
                editor.firstChild.innerHTML = data;
            }
        });

        document.getElementById("audioPlayer").load();

        //Change messages displayed from ROCCGetMessages.php
        let messagesContainer = document.getElementById("messagesContainer");
        messagesContainer.innerHTML = "";
        for (let message of messages) {
            addMessage(message);
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
}

function saveNotes(slideNumber) {
    var myEditor = document.querySelector('#editor-container');
    var notes = myEditor.children[0].innerHTML;
    console.log(notes, CURR_STORY); 
    //document.getElementById("textField").value;
    $.ajax({
        data: "notes=" + notes +
        "&slideNumber=" + slideNumber +
        "&storyId=" + CURR_STORY,
        url: "/API/submitNote.php",
        data: "notes=" + notes + "&slideNumber=" + slideNumber + "&storyId=" + CURR_STORY,
        url: "submitNote.php",
        type: "POST",
        success: function () {
	}
    });
}



function approveSwitchChanged(slideNumber, checkbox) {
   
    var div = document.getElementById("thumbnail " + slideNumber);
    if(checkbox.checked){
    	div.setAttribute("style", "border-color: red;"); 
    }
    else{
    	div.removeAttribute("style", "border-color:red;");
    }

    if (checkbox.checked !== approvals[slideNumber]) {
        approvals[slideNumber] = checkbox.checked;
        $.ajax({
            data: "slideNumber=" + slideNumber +
            "&storyId=" + CURR_STORY +
            "&projectId=" + CURR_PROJ +
            "&isApproved=" + checkbox.checked,
            url: "/API/setSlideApproval.php",
            method: "POST",
            success: function (data) {
                console.log(data);
            }
        });
    }
}

function saveWholeNotes() {
    var myEditor = document.querySelector('#editor-container2');
    var notes = myEditor.children[0].innerHTML;
    //document.getElementById("textField").value;
    $.ajax({
        data: "notes=" + notes +
        "&storyId=" + CURR_STORY,
        url: "/API/submitWholeStoryNote.php",
        type: "POST",
        success: function () {
        }
    });
}

function sendMessage(CURR_SLIDE){

    var slideNumber = CURR_SLIDE;
    var text = document.getElementById("sendMessage").value;
    console.log(CURR_SLIDE);
    $.ajax({
        data: "slideNumber=" + slideNumber + "&storyId=" + CURR_STORY + "&text=" + text,
        url: "API/ROCCSendMessage.php",
        type: "POST",
        success: function (data) {
            console.log(data);
        }
    });


function sendMessage(slideNumber){
    let message = {
        'storyId': 0,
        'slideNumber': slideNumber,
        'text': document.getElementById("sendMessage").value,
    };
    connection.send(JSON.stringify(message));
}

new Quill('#editor-container', {
    modules: {
        toolbar: [
            ['bold', 'italic', 'blockquote'],
            [{list: 'ordered'}, {list: 'bullet'}]
        ]
    },
    placeholder: 'Enter Notes',
    theme: 'snow'
});

new Quill('#editor-container2', {
    modules: {
        toolbar: [
            ['bold', 'italic'],
            [{list: 'ordered'}, {list: 'bullet'}]
        ]
    },
    placeholder: 'Enter Notes',
    theme: 'snow'
});

let connection = connect();
