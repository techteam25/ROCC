function initPage() {

	console.log("running init on page");
	for(var i = 0; i < projects.length; i++){

		var langDiv = document.getElementById("lang");
		var nameDiv = document.createElement("div");
		nameDiv.innerHTML = projects[i]; 
		nameDiv.id = projects[i]; 
		nameDiv.className = "langDiv";
		langDiv.appendChild(nameDiv);
	}
	var initLang = projects[0]; 
	var langTitle = document.getElementById("pSD"); 
	langTitle.innerHTML = '';

	//check for no projects assigned
	if(initLang !== undefined){
		langTitle.innerHTML = initLang;
	}
	else {
		langTitle.innerHTML = "No projects assigned.";
	}

	$.ajax({
		data: "language=" + initLang,
		url: "API/getLangInfo.php",
		type: "POST",
		success: function (data){
    			console.log(data);
			reloadPage(data);
		} 
	});
}

function reloadStoryInfo(storyId){
	var bar_info = document.getElementById("bar-info");
	var apr_info = document.getElementById("p-info");

	//resets the divs to new information
	while (bar_info.children.length > 1) {
		bar_info.removeChild(bar_info.lastChild);
	}
	apr_info.textContent= ''; 

	//recreates all the divs containg information
	var aprSlide = document.createElement("p1");
	var dprSlide = document.createElement("p2");
	var totalSlide = document.createElement("p2");
	var bar = document.createElement("bar");
	
	bar.id = "bar";
	bar_info.appendChild(bar);

	$.ajax({
		data: "storyId=" + storyId,
		url: "API/getStoryInfo.php",
		type: "POST",
		success: function (data){

			var apprArray=JSON.parse(data);
			console.log(apprArray);
			var total = 0;
			var ap = 0;
			var dp = 0;
			for (const key of apprArray){
				total++;
				if(key.isApproved == 1){
					ap ++;
				}
				else{
					dp++
				}
			}

			var bar=new RadialProgress(document.getElementById("bar"),{colorBg: "#FFFFFF", colorFg:"#212238",thick:2.8,fixedTextSize:0.2});
			var perc = ap / total;
			if(isNaN(perc)){
				perc = 0;
			}
			
			bar.setValue(perc);
			bar.round = true;
			bar.animationSpeed = 3;

			totalSlide.innerHTML = "Total Slides: " + total; 
			totalSlide.id = "totalSlide";
			bar_info.appendChild(totalSlide);

			aprSlide.innerHTML = ap + " Approved"; 
			dprSlide.innerHTML = dp + " Unapproved"; 
			apr_info.appendChild(aprSlide);
			apr_info.appendChild(dprSlide);

		} 
	});
}

function reloadStoryTitle(titleArray) {

	var titleDiv = document.getElementById('storyList');
	titleDiv.innerHTML = '';
	var bar_info = document.getElementById("bar-info");
	var apr_info = document.getElementById("p-info");
	var keyData = titleArray[0];

	//resets the divs to new information
	while (bar_info.children.length > 1) {
		bar_info.removeChild(bar_info.lastChild);
	}
	apr_info.textContent= ''; 
	
	var index = 0;
	document.getElementById("view_story").href = "";
	document.getElementById("img_link").href = "";
	document.getElementById("csRightLink").href = "";

	for (const key of titleArray){
		var storyDiv = document.createElement("div");
		var perc = document.createElement("p");
		var storyTitle = document.createElement("p");
		storyTitle.innerHTML = key.title;
		storyDiv.id = index; 
		storyDiv.className= "storyDivClass";
		percentage = (key.approved * 100);
		perc.innerHTML = percentage.toFixed(0) + '%';
		perc.style.fontSize = '15px';
		if(key.approved == 1){
			storyDiv.style.color = 'green';
		}
		else if(key.approved == 999){
			perc.innerHTML = 'EMPTY';  
			storyDiv.style.color = '#80808091';
		}
		storyDiv.appendChild(storyTitle);
		storyDiv.appendChild(perc);
		titleDiv.appendChild(storyDiv);
		index ++;
	}
	var img = document.getElementById("img_thumb");  
	img.src ='Files/Templates/' + keyData.currProjId + "/" + keyData.storyId + '/1.jpg'; 
	img.onerror = function() {
		img.src ='Files/Templates/' + keyData.currProjId + "/" + keyData.storyId + '/1.png'; 
		img.onerror = function() {
			document.getElementById('img_link').src = "images/noimg.png"; 
		}	
	}
}

function reloadStoryImg(titleArray) {
	console.log("inrimg");
	var keyData = titleArray[0];
	csRightLink.innerHTML = '';
	var vs = document.getElementById("view_story");
	if (typeof keyData !== 'undefined'){
		csRightLink.innerHTML = keyData.title;
		var img = document.getElementById("img_thumb");  
		img.src =`Files/Templates/${keyData.title}/1.jpg`; 
		img.onerror = function() {
			img.src =`Files/Templates/${keyData.title}/1.png`; 
			img.onerror = function() {
				img.src = "images/noimg.png";
			}
		}	

		vs.href=  "client.php?story="+ keyData.storyId;
		document.getElementById("img_link").href = "client.php?story=" + keyData.storyId;
		document.getElementById("csRightLink").href = "client.php?story=" + keyData.storyId;
	}
		

}

function reloadPage(data) {
 
	var vs = document.getElementById("view_story");  

	var titleArray = JSON.parse(data);
	if(isEmpty(titleArray)){
		let no_story = document.getElementById('csLeft');
		no_story.innerHTML = '';
		let ns_error = document.createElement("p");
		ns_error.innerHTML = 'No Story Associated with this Project';
		no_story.appendChild(ns_error);
		
	}

	else if(typeof titleArray[0] !== 'undefined'){
		reloadStoryInfo(titleArray[0].storyId);
		reloadStoryTitle(titleArray);
		reloadStoryImg(titleArray);
	}

	//change on story click

	//change on click
	$(".storyDivClass").click(function() {
		var clickedId = jQuery(this).attr("id");
		var keyData = titleArray[clickedId];
		if (typeof keyData !== 'undefined'){
			var storyTitle=keyData.title;
			var storyId=keyData.storyId;
			reloadStoryInfo(storyId);
			//reloads the image and story title
			var img = document.getElementById("img_thumb"); 
			var imgLink = document.getElementById("img_link");
			csRightLink.innerHTML = '';
			csRightLink.innerHTML = storyTitle; 
        		img.src =`Files/Templates/${keyData.title}/1.jpg`; 
			img.onerror = function() { 
        			img.src =`Files/Templates/${keyData.title}/1.png`; 
				img.onerror = function() { 
					document.getElementById('img_thumb').src = "images/noimg.png"; 
				}
			}
			vs.href=  "client.php?story="+ keyData.storyId;
			imgLink.href=  "client.php?story="+ keyData.storyId;
			document.getElementById("csRightLink").href=  "client.php?story="+ keyData.storyId;
			console.log(img.href);
		}
	}); 
}

function isEmpty(obj) {
    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return false;
    }
    return true;
}
