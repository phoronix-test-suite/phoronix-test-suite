function delete_result_file(id)
{
	if(confirm("Really delete the '" + id + "' result file permanently?"))
	{
		window.location.href = WEB_URL_PATH + "index.php?remove_result=" + id;
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
	xhttp.open("POST", WEB_URL_PATH + "index.php?page=update-result-file-meta", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("result_file_id=" + id + "&result_title=" + title + "&result_desc=" + description);
}
function delete_result_from_result_file(result_file, result_hash)
{
	if(confirm("Permanently delete this result graph?"))
	{
		document.getElementById("result-" + result_hash).style.display = "none";
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {

			}
		};
		xhttp.open("POST", WEB_URL_PATH + "index.php?page=remove-result-object", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("result_file_id=" + result_file + "&result_object=" + result_hash);
	}
	return false;
}
function hide_result_in_result_file(result_file, result_hash)
{
	document.getElementById("result-" + result_hash).style.display = "none";
}
function delete_run_from_result_file(result_file, system_identifier, ppd)
{
	if(confirm("Permanently delete this '" + system_identifier + "' run?"))
	{
		document.getElementById("table-line-" + ppd).style.display = "none";
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {

			}
		};
		xhttp.open("POST", WEB_URL_PATH + "index.php?page=remove-result-run", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("result_file_id=" + result_file + "&result_run=" + system_identifier);
		setTimeout(function(){window.location.reload(1);}, 2500);
	}
	return false;
}
function rename_run_in_result_file(result_file, system_identifier)
{
	var new_system_identifier = prompt("Please enter new result identifier:", system_identifier);
	if(new_system_identifier == "")
	{
		alert("Result identifier cannot be empty!");
	}
	else if(confirm("Confirm changing '" + system_identifier + "' to '" + new_system_identifier + "'?"))
	{
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {

			}
		};
		xhttp.open("POST", WEB_URL_PATH + "index.php?page=rename-result-run", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("result_file_id=" + result_file + "&result_run=" + system_identifier + "&new_result_run=" + new_system_identifier);
		setTimeout(function(){window.location.reload(1);}, 1500);

	}
	return false;
}
function display_add_annotation_for_result_object(result_file, result_hash, link_obj)
{
	link_obj.style.display = "none";
	document.getElementById("annotation_area_" + result_hash).style.display = "inline";
}
function add_annotation_for_result_object(result_file, result_hash, form)
{
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
	if(this.readyState == 4 && this.status == 200) {
		location.reload();
		}
	};
	xhttp.open("POST", WEB_URL_PATH + "index.php?page=add-annotation-to-result-object", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("result_file_id=" + result_file + "&result_object=" + result_hash + "&annotation=" + form.annotation.value);
}
function update_annotation_for_result_object(result_file, result_hash)
{
	var annotation_updated = document.getElementById("update_annotation_" + result_hash).textContent;
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
	if(this.readyState == 4 && this.status == 200) {
		location.reload();
		}
	};
	xhttp.open("POST", WEB_URL_PATH + "index.php?page=add-annotation-to-result-object", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("result_file_id=" + result_file + "&result_object=" + result_hash + "&annotation=" + annotation_updated);
}
function display_test_logs_for_result_object(result_file, result_hash)
{
	window.open(WEB_URL_PATH + "index.php?page=view_test_logs&result_file_id=" + result_file + "&result_object=" + result_hash, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=600,height=400,location=no,menubar=no");
}
function display_install_logs_for_result_object(result_file, result_hash)
{
	window.open(WEB_URL_PATH + "index.php?page=view_install_logs&result_file_id=" + result_file + "&result_object=" + result_hash, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=600,height=400,location=no,menubar=no");
}
function display_system_logs_for_result(result_file, system_id)
{
	window.open(WEB_URL_PATH + "index.php?page=view_system_logs&result_file_id=" + result_file + "&system_id=" + system_id, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=600,height=400,location=no,menubar=no");
}
function reorder_result_file(result_file)
{
	window.open(WEB_URL_PATH + "index.php?page=reorder_result_file&result_file_id=" + result_file, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=600,height=400,location=no,menubar=no");
}
function invert_hide_all_results_checkboxes()
{
	var inputs = document.getElementsByName("rmm[]");
	for(var i = 0; i < inputs.length; i++)
	{
		if(inputs[i].type == "checkbox")
		{
			inputs[i].checked = !inputs[i].checked;
		}
	}
}
