function delete_result_file(id)
{
	if(confirm("Really delete the '" + id + "' result file permanently?"))
	{
		window.location.href = "/index.php?remove_result=" + id;
	}
}
function edit_result_file_meta()
{
	 document.getElementById("result_file_title").contentEditable = "true";
	 document.getElementById("result_file_desc").contentEditable = "true";
	 document.getElementById("result_file_title").style.border = "1px solid #AAA";
	 document.getElementById("result_file_desc").style.border = "1px solid #AAA";
	 document.getElementById("edit_result_file_meta_button").style.display = "none";
	 document.getElementById("save_result_file_meta_button").style.display = "inline";
}
function save_result_file_meta(id)
{
	var title = document.getElementById("result_file_title").textContent;
	var description = document.getElementById("result_file_desc").textContent;
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
	if(this.readyState == 4 && this.status == 200) {
		location.reload();
		}
	};
	xhttp.open("POST", "/index.php?page=update-result-file-meta", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("result_file_id=" + id + "&result_title=" + title + "&result_desc=" + description);
}
