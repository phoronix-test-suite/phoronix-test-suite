var phoromatic_results_clicked = new Array();

function phoromatic_click_results(new_id)
{
	if(phoromatic_results_clicked.indexOf(new_id) != -1)
	{
		window.location.href = "?result/" + new_id;
	}
	else
	{
		document.getElementById("result_select_" + new_id).style.background = "#949494";
		phoromatic_results_clicked.push(new_id);
	}

	var new_button_area = "<p>";

	if(phoromatic_results_clicked.length > 0)
	{
		var plurality = "";
		if(phoromatic_results_clicked.length > 1)
			plurality = "s";

		new_button_area += " <button type=\"button\" onclick=\"javascript:phoromatic_delete_results();\">Delete Result" + plurality + "</button>";
		new_button_area += " <button type=\"button\" onclick=\"javascript:phoromatic_compare_results();\">Compare Result" + plurality + "</button>";
	}
	new_button_area += "</p>";

	document.getElementById("pts_phoromatic_bottom_result_button_area").innerHTML = new_button_area;
	document.getElementById("pts_phoromatic_top_result_button_area").innerHTML = new_button_area;
}
function phoromatic_jump_to_results_from(schedule_id, select_id, prepend_results = "")
{
	var time_since = pts_get_list_item(select_id);
	window.location.href = "?result/" + prepend_results + "S:" + schedule_id + ":" + time_since;
}
function phoromatic_compare_results()
{
	if(phoromatic_results_clicked.length > 1)
	{
		window.location.href = "?result/" + phoromatic_results_clicked.join(",");
	}
}
function phoromatic_delete_results()
{
	if(phoromatic_results_clicked.length > 0 && confirm("Press OK to delete these results!"))
	{
		window.location.href = "?results/delete/" + phoromatic_results_clicked.join(",");
	}
}
function phoromatic_initial_registration(form)
{
	if(form.register_username.value.length < 4 || form.register_username.value.indexOf(" ") != -1)
	{
		alert("Please enter a user-name of at least four characters, without spaces.");
		return false;
	}
	if(form.register_password.value.length < 4)
	{
		alert("Please enter a password of at least four characters.");
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
function phoromatic_login(form)
{
	if(form.username.value.length < 4)
	{
		alert("Please enter a valid username; it should be at least four characters long.");
		return false;
	}
	if(form.password.value.length < 4)
	{
		alert("Please enter a valid password; it should be at least four characters long.");
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
function phoromatic_schedule_test_details(append_args = "")
{
	document.getElementById("test_details").innerHTML = "";
	var test_target = pts_get_list_item("add_to_schedule_select_test");
	phoromatic_ajax_update_element("r_add_test_details/&tp=" + test_target + append_args, "test_details");
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
