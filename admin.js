function SearchLan() {
	var input, filter, table, tr, td, i, txtValue;
	input = document.getElementById("searchForLan");
	filter = input.value.toUpperCase();
	table = document.getElementById("users-table");
	tr = table.getElementsByTagName("tr");

	for(i=0; i < tr.length; i++) {
		td = tr[i].getElementsByTagName("td")[1];
		if(td) {
			txtValue = td.textContent || td.innerText;
			if (txtValue.toUpperCase().indexOf(filter) > -1) {
				tr[i].style.display = "";
			}
			else {
				tr[i].style.display = "none";
			}
		}
	}
}

function SearchName() {
	var input, filter, table, tr, td, i, txtValue;
	input = document.getElementById("searchForName");
	filter = input.value.toUpperCase();
	table = document.getElementById("assign-table");
	console.log(table);
	tr = table.getElementsByTagName("tr");

	for(i=0; i < tr.length; i++) {
		td = tr[i].getElementsByTagName("td")[0];
		if(td) {
			txtValue = td.textContent || td.innerText;
			if (txtValue.toUpperCase().indexOf(filter) > -1) {
				tr[i].style.display = "";
			}
			else {
				tr[i].style.display = "none";
			}
		}
	}
}


function remove(email) {
	if(confirm("Are you sure you want to delete " + email + "?")){
		$.ajax({
			data: "Email=" + email,
			url: "API/RemoveConsultant.php",
			type: "POST",
			success: function(){
				location.reload(true);
			}
		});
	}
}

function remove_assignment(conId, projId) {
	if(confirm("Are you sure you want to remove this assignment?")){
		$.ajax({
			data: `conId=${conId}&projId=${projId}`,
			url: "API/RemoveAssignment.php",
			type: "POST",
			success: function(data){
				location.reload(true);
			}
		});
	}
}

