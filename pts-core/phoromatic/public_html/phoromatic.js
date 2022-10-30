function phoromatic_clear_results_search_fields()
{
	document.getElementById("containing_tests").value = "";
	document.getElementById("containing_hardware").value = "";
	document.getElementById("containing_software").value = "";
	document.getElementById("search_for").value = "";
}
function phoromatic_toggle_checkboxes_on_page(global_checkbox)
{
	var inputs = document.getElementsByTagName("input");
	var pprid;
	var check_boxes = global_checkbox.checked;
	for(var i = 0; i < inputs.length; i++)
	{
		if(inputs[i].type == "checkbox" && inputs[i].id.indexOf("result_compare_checkbox_") != -1)
		{
			pprid = inputs[i].id.substr(24);
			if(check_boxes && inputs[i].checked == false)
			{
				// check the box
				inputs[i].checked = true;
				phoromatic_checkbox_toggle_result_comparison(pprid);
			}
			else if(check_boxes == false && inputs[i].checked)
			{
				// uncheck the box
				inputs[i].checked = false;
				phoromatic_checkbox_toggle_result_comparison(pprid);
			}
		}
	}
}
function phoromatic_window_redirect(url)
{
	window.location.href = url;
}
function phoromatic_delete_results(ext)
{
	if(typeof(Storage) !== 'undefined' && localStorage.comparison_pprids)
	{
		var ids = JSON.parse(localStorage.comparison_pprids);

		if(ids.length > 0 && confirm("Press OK to delete the " + ids.length + " selected results."))
		{
			localStorage.removeItem("comparison_pprids");
			window.location.href = ext + ids.join();
		}
	}
}
function phoromatic_checkbox_toggle_result_comparison(pprid)
{
	if(typeof(Storage) !== 'undefined')
	{
		if(localStorage.comparison_pprids)
		{
			var ids = JSON.parse(localStorage.comparison_pprids);
		}
		else
		{
			var ids = [];
		}

		if(pprid != '')
		{
			if(ids.indexOf(pprid) == -1)
			{
				// Add the PPRID to comparison
				ids.push(pprid);
			}
			else
			{
				ids.splice(ids.indexOf(pprid), 1);
				if(document.getElementById("result_select_" + pprid))
				{
					document.getElementById("result_select_" + pprid).style.background = "#f1f1f1";
				}
				document.getElementById("result_compare_checkbox_" + pprid).checked = false;
			}

			localStorage.comparison_pprids = JSON.stringify(ids);
		}

		if(ids.length > 0)
		{
			for(var i = 0; i < ids.length; i++)
			{
				if(document.getElementById("result_select_" + ids[i]))
				{
					document.getElementById("result_select_" + ids[i]).style.background = "#949494";
				}
				if(document.getElementById("result_compare_link_" + ids[i]))
				{
					document.getElementById("result_compare_link_" + ids[i]).innerHTML = "Remove From Comparison";
				}
				if(document.getElementById("result_delete_link_" + ids[i]))
				{
					document.getElementById("result_delete_link_" + ids[i]).style.visibility = 'hidden';
				}
				if(ids.length > 1 && document.getElementById("result_run_compare_link_" + ids[i]))
				{
					document.getElementById("result_run_compare_link_" + ids[i]).innerHTML = 'Compare Results (' + ids.length + ')';
					document.getElementById("result_run_compare_link_" + ids[i]).style.visibility = 'visible';
				}
				if(document.getElementById("result_compare_checkbox_" + ids[i]))
				{
					document.getElementById("result_compare_checkbox_" + ids[i]).checked = true;
				}
			}

			document.getElementById("phoromatic_result_selected_info_box").innerHTML = ids.length + " Selected Results";
			document.getElementById("phoromatic_result_selected_info_box").style.display= 'block';
			document.getElementById("phoromatic_result_compare_info_box").style.display = 'block';
			document.getElementById("phoromatic_result_delete_box").style.display = 'block';
		}
		else
		{
			document.getElementById("phoromatic_result_selected_info_box").style.display = 'none';
			document.getElementById("phoromatic_result_compare_info_box").style.display = 'none';
			document.getElementById("phoromatic_result_delete_box").style.display = 'none';
		}
	}

	return false;
}
function toggle_annotate_area(annotate_hash)
{
	document.getElementById("annotation_link_" + annotate_hash).style.display = 'none';
	document.getElementById("annotation_area_" + annotate_hash).style.display = 'block';
}
function phoromatic_generate_comparison(ext)
{
	if(typeof(Storage) !== 'undefined' && localStorage.comparison_pprids)
	{
		var ids = JSON.parse(localStorage.comparison_pprids);
		localStorage.removeItem("comparison_pprids");
		window.location.href = ext + ids.join();
	}
}
function phoromatic_jump_to_results_from(schedule_id, select_id, prepend_results)
{
	var time_since = pts_get_list_item(select_id);
	window.location.href = "?result/" + prepend_results + "S:" + schedule_id + ":" + time_since;
}
function phoromatic_do_custom_compare_results(form)
{
	var compare_boxes = document.getElementsByName('compare_results');
	var pprids_compare = [];

	for(var i = 0; i < compare_boxes.length; i++)
	{
		if(compare_boxes[i].checked)
			pprids_compare.push(compare_boxes[i].value);
	}
	window.location.href = "?result/" + document.getElementById('compare_similar_results_this').value + "," + pprids_compare.join();
}
function phoromatic_initial_registration(form)
{
	if(form.register_username.value.length < 4 || form.register_username.value.indexOf(" ") != -1)
	{
		alert("Please enter a user-name of at least four characters, without spaces.");
		return false;
	}
	if(form.register_password.value.length < 6)
	{
		alert("Please enter a password of at least six characters.");
		return false;
	}
	if(form.register_password_confirm.value != form.register_password.value)
	{
		alert("The supplied passwords do not match.");
		return false;
	}

	if(form.register_email.value.length < 5)
	{
		alert("Please enter a valid email address.");
		return false;
	}

	var email_at = form.register_email.value.indexOf("@");
	var email_dot = form.register_email.value.lastIndexOf(".");

	if(email_at < 1 || email_dot < (email_at + 2) || email_dot + 2 >= form.register_email.value.length)
	{
		alert("Please enter a valid email address.");
		return false;
	}

	var valid_username_chars = '1234567890-_.abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	for(var i = 0; i < form.register_username.value.length; i++)
	{
		if(valid_username_chars.indexOf(form.register_username.value.substr(i, 1)) == -1)
		{
			alert("The username contains an invalid character: " + form.register_username.value.substr(i, 1));
			return false;
		}
	}

	return true;
}
function phoromatic_password_reset(form)
{
	if(form.old_password.value.length < 6)
	{
		alert("Please enter a valid password; it should be at least six characters long.");
		return false;
	}
	if(form.register_password.value.length < 6)
	{
		alert("Please enter a valid password; it should be at least six characters long.");
		return false;
	}
	if(form.register_password.value != form.register_password_confirm.value)
	{
		alert("The new passwords do not match.");
		return false;
	}

	return true;
}
function phoromatic_login(form)
{
	if(form.username.value.length < 4)
	{
		alert("Please enter a valid username; it should be at least four characters long.");
		return false;
	}
	if(form.password.value.length < 6)
	{
		alert("Please enter a valid password; it should be at least six characters long.");
		return false;
	}

	return true;
}
function phoromatic_system_edit(form)
{
	if(form.system_title.value.length < 3)
	{
		alert("Please enter a system title of at least three characters.");
		return false;
	}
	if(form.system_description.value.length == 0)
	{
		alert("Please enter a system description.");
		return false;
	}
	return true;
}
function phoromatic_new_group(form)
{
	if(form.new_group.value.length < 3)
	{
		alert("Please enter a name for the new system group.");
		return false;
	}

	var valid_group_chars = ' 1234567890-_.abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	for(var i = 0; i < form.new_group.value.length; i++)
	{
		if(valid_group_chars.indexOf(form.new_group.value.substr(i, 1)) == -1)
		{
			alert("The group name contains an invalid character: " + form.new_group.value.substr(i, 1));
			return false;
		}
	}
	return true;
}
function phoromatic_schedule_test_details(append_args)
{
	document.getElementById("test_details").innerHTML = "";
	var test_target = pts_get_list_item("add_to_schedule_select_test");
	phoromatic_ajax_update_element("r_add_test_details/&tp=" + test_target + append_args, "test_details");
}
function phoromatic_show_basic_suite_details(append_args)
{
	document.getElementById("suite_details").innerHTML = "";
	var suite_target = pts_get_list_item("suite_to_run_identifier");
	phoromatic_ajax_update_element("r_basic_suite_details/&ts=" + suite_target + append_args, "suite_details");
}
function phoromatic_build_suite_test_details()
{
	var test_target = pts_get_list_item("add_to_suite_select_test");
	if(test_target)
	{
		phoromatic_ajax_append_element("r_add_test_build_suite_details/&tp=" + test_target, "test_details");
	}
	document.getElementById("add_to_suite_select_test").selectedIndex = 0;
}
function pts_get_list_item(select_id)
{
	var item_value = document.getElementById(select_id).options[document.getElementById(select_id).selectedIndex].value;

	if(pts_is_int_string(item_value))
	{
		if(item_value[0] == "0" && item_value.length > 1)
			item_value = item_value.substring(1);

		item_value = parseInt(item_value);
	}

	return item_value;
}
function pts_is_int_string(str)
{
	for(var i = 0; i < str.length; i++)
	{
		var ch = str[i];

		if(ch != 0 && ch != 1 && ch != 2 && ch != 3 && ch != 4 && ch != 5 && ch != 6 && ch != 7 && ch != 8 && ch != 9)
			return false;
	}
	return true;
}
function phoromatic_test_select_update_selected_name(select_obj)
{
	var select_id = select_obj.id;
	var select_name = document.getElementById(select_id).options[document.getElementById(select_id).selectedIndex].innerHTML;

	document.getElementById(select_id + "_selected").value = document.getElementById(select_id + "_name").innerHTML + ": " + select_name;
}
function phoromatic_test_select_update_selected_name_custom_input(in_obj)
{
	var select_id = in_obj.id;
	document.getElementById(select_id + "_selected").value = document.getElementById(select_id + "_name").innerHTML + ": " + in_obj.value;
}
function pts_ajax_request_object()
{
	var request_;
	var browser = navigator.appName;

	if(browser == "Microsoft Internet Explorer")
	{
		request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}
	else
	{
		request_ = new XMLHttpRequest();
	}
	return request_;
}
function phoromatic_remove_from_suite_list(eid)
{
	var dnode = document.getElementById(eid);
	while(dnode.firstChild)
	{
		dnode.removeChild(dnode.firstChild);
	}
}
function phoromatic_ajax_update_element(r, d)
{
	var http = new Array();
	var rnow = new Date();
	http[rnow] = pts_ajax_request_object();
	http[rnow].open("get", "index.php?" + r, true);
	http[rnow].onreadystatechange = function(){
		if(http[rnow].readyState == 4)
		{
			if(http[rnow].status == 200 || http[rnow].status == 304)
			{
				document.getElementById(d).innerHTML = http[rnow].responseText;
			}
		}}
	http[rnow].send(null);
}
function phoromatic_ajax_append_element(r, d)
{
	var http = new Array();
	var rnow = new Date();
	http[rnow] = pts_ajax_request_object();
	http[rnow].open("get", "index.php?" + r, true);
	http[rnow].onreadystatechange = function(){
		if(http[rnow].readyState == 4)
		{
			if(http[rnow].status == 200 || http[rnow].status == 304)
			{
				var container = document.createElement("div");
				container.innerHTML = http[rnow].responseText;
				document.getElementById(d).appendChild(container);
			}
		}}
	http[rnow].send(null);
}
function display_system_logs_for_result(result_file, system_id)
{
	window.open(window.location.href + "&export=view_system_logs&result_file_id=" + result_file + "&system_id=" + btoa(system_id), "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=800,height=600,location=no,menubar=no");
}
function display_test_logs_for_result_object(result_file, result_hash, select_identifier)
{
	window.open(window.location.href + "&export=view_test_logs&result_file_id=" + result_file + "&result_object=" + result_hash + "&log_select=" + select_identifier, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=800,height=600,location=no,menubar=no");
}
function display_install_logs_for_result_object(result_file, result_hash, select_identifier)
{
	window.open(window.location.href + "&export=view_install_logs&result_file_id=" + result_file + "&result_object=" + result_hash + "&log_select=" + select_identifier, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=800,height=600,location=no,menubar=no");
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
	xhttp.open("POST", window.location.href + "&modify=update-result-file-meta", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("&result_title=" + title + "&result_desc=" + description);
}
function delete_result_from_result_file(result_file, result_hash)
{
	if(confirm("Permanently delete this result graph?"))
	{
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {

			}
		};
		xhttp.open("POST", window.location.href + "&modify=remove-result-object", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("&result_object=" + result_hash);
		document.getElementById("r-" + result_hash).style.display = "none";
	}
	return false;
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
		xhttp.open("POST", window.location.href + "&modify=remove-result-run", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("&result_run=" + encodeURIComponent(system_identifier));
		setTimeout(function(){window.location.reload(1);}, 2500);
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
	xhttp.open("POST", window.location.href + "&modify=add-annotation-to-result-object", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("&result_object=" + result_hash + "&annotation=" + form.annotation.value);
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
	xhttp.open("POST", window.location.href + "&modify=add-annotation-to-result-object", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("&result_object=" + result_hash + "&annotation=" + annotation_updated);
}
function reorder_result_file(result_file)
{
	window.open(window.location.href + "&modify=reorder_result_file", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=100,width=600,height=400,location=no,menubar=no");
}
function rename_run_in_result_file(result_file, system_identifier)
{
	var new_system_identifier = prompt("Please enter new result identifier:", system_identifier);
	if(new_system_identifier == "" || new_system_identifier == null)
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
		xhttp.open("POST", window.location.href + "&modify=rename-result-run", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("result_file_id=" + result_file + "&result_run=" + encodeURIComponent(system_identifier) + "&new_result_run=" + encodeURIComponent(new_system_identifier));
		setTimeout(function(){window.location.reload(1);}, 1500);

	}
	return false;
}
