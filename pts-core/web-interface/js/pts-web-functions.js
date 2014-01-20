function search_results(j)
{
	var logo = document.getElementById("search_loading_area");
	logo.parentNode.removeChild(logo);

	document.getElementById("search_results").innerHTML = "";

	if(j.pts.status && j.pts.status.error)
	{
		document.getElementById("search_results").innerHTML = "<h2>" + j.pts.status.error + "</h2>";
	}
	else
	{
		document.getElementById("search_results").innerHTML += "<p>Search results for <strong>" + j.pts.msg.search_query + "</strong>:</p>";

		if(j.pts.msg.tests.length == 0)
		{
			document.getElementById("search_results").innerHTML += "<p><strong>No matching tests found.</strong></p>";
		}
		else
		{
			document.getElementById("search_results").innerHTML += "<p><strong>Matching test profiles.</strong></p>";
		}

		for(var i = 0; i < j.pts.msg.tests.length; i++)
		{
			var test = j.pts.msg.tests[i];
			var test_profile = JSON.parse(atob(j.pts.msg.test_profiles[i]));
			document.getElementById("search_results").innerHTML += "<h2><a href=\"?test/" + test + "\">" + test_profile.TestInformation.Title + "</a></h2>";

			var test_description = test_profile.TestInformation.Description;
			if(j.pts.msg.exact_hits == 0)
			{
				test_description = test_description.replace(new RegExp(j.pts.msg.search_query, 'g'), '<strong>' + j.pts.msg.search_query + '</strong>');
			}
			document.getElementById("search_results").innerHTML += "<p>" + test_description + "</p>";
		}

		document.getElementById("search_results").innerHTML += "<hr />";

		if(j.pts.msg.results.length > 0)
		{
			if(j.pts.msg.tests.length == 0)
			{
				document.getElementById("search_results").innerHTML += "<p><strong>Test results referencing " + j.pts.msg.search_query + ".</strong></p>";
			}
			else
			{
				document.getElementById("search_results").innerHTML += "<p><strong>Test results containing these tests.</strong></p>";
			}
		}

		for(var i = 0; i < j.pts.msg.results.length; i++)
		{
			var result_file = JSON.parse(atob(j.pts.msg.result_files[i]));
			document.getElementById("search_results").innerHTML += "<h2><a href=\"?result/" + j.pts.msg.results[i] + "\">" + result_file.Generated.Title + "</a></h2>";
			document.getElementById("search_results").innerHTML += "<p>" + result_file.Generated.Description + "</p>";
		}
	}
}
function display_results_by_date(j)
{
	var results = JSON.parse(atob(j.pts.msg.results));
	var date_sections = new Array();
	var date_titles = new Array();

	var today = new Date();
	today.setHours(0, 0, 0, 0);
	date_sections.push(today);
	date_titles.push("Today");

	var this_month = new Date();
	this_month.setHours(0, 0, 0, 0);
	this_month.setDate(1);
	date_sections.push(this_month);
	date_titles.push("This Month");

	var last_month = new Date();
	last_month.setDate(1);
	last_month.setMonth(last_month.getMonth() - 1);
	last_month.setHours(0, 0, 0, 0);
	date_sections.push(last_month);
	date_titles.push("Last Month");

	if(today.getMonth() > 2)
	{
		var this_year = new Date();
		this_year.setDate(1);
		this_year.setMonth(0);
		this_year.setHours(0, 0, 0, 0);
		date_sections.push(this_year);
		date_titles.push("Earlier This Year");
	}

	var last_year = new Date();
	last_year.setDate(31);
	last_year.setMonth(11);
	last_year.setHours(0, 0, 0, 0);
	last_year.setFullYear(last_year.getFullYear() - 1);
	date_sections.push(last_year);
	date_titles.push("Last Year");

	var last_yeart = new Date();
	last_yeart.setDate(1);
	last_yeart.setMonth(0);
	last_yeart.setHours(0, 0, 0, 0);
	last_yeart.setFullYear(last_yeart.getFullYear() - 2);
	date_sections.push(last_yeart);
	date_titles.push("Two Years Ago");

	var current_section = date_sections.length - 1;
	var prepend = "";

	for(var k in results)
	{
		var d = new Date(k * 1000);

		if(d > date_sections[current_section])
		{
			if(prepend != "")
				prepend = "</div><h2>" + date_titles[current_section] + "</h2><div style=\"overflow: hidden;\">" + prepend;

			while(current_section > 0 && d > date_sections[current_section])
			{
				current_section--;
			}
		}
		else if(d < last_year)
		{
			continue;
		}

		prepend = "<a href=\"?result/" + k + "\"><div class=\"pts_blue_bar\"><strong>" + results[k] + "</strong><br /><span style=\"font-size: 10px;\">" + "date" + " - " + "system count System(s)" + "result count(s)" + "</span></div></a>" + prepend;
	}

	document.getElementById("results_linear_display").innerHTML += prepend;
}

function display_grouped_results_by_date(j)
{
	var results = j.pts.msg.results;
	pts_web_socket.add_onmessage_event("result_file", "update_result_box");
	var result_files = ""

	for(var k in results)
	{
		document.getElementById("results_linear_display").innerHTML += "<h2 style=\"clear: both;\">" + k + "</h2><div style=\"overflow: hidden;\">";

		for(var i = 0; i < results[k].length; i++)
		{
			document.getElementById("results_linear_display").innerHTML += "<a href=\"?result/" + results[k][i] + "\"><div class=\"pts_blue_bar\" id=\"result_" + b64id(results[k][i]) + "\"><strong>" + results[k][i] + "</strong></div></a>";
			result_files += results[k][i] + ",";
		}

		document.getElementById("results_linear_display").innerHTML += "</div>";
	}
	pts_web_socket.send("result_file " + result_files);
}
function update_version_string(j)
{
	document.getElementById("pts_version").innerHTML = j.pts.msg.version;
}
function b64id(i)
{
	var id = btoa(i);
	var t = id.indexOf('=');

	if(t != -1)
	{
		id = id.substr(0, t);
	}

	return id;
}
function plural_handler(count, base)
{
	var str = count + " " + base;

	if(count > 1)
	{
		str += "s";
	}

	return str;
}
function update_result_box(j)
{
	var result_file = JSON.parse(atob(j.pts.msg.result_file));
	var result = j.pts.msg.result;

	if(document.getElementById("result_" + b64id(result)))
	{
		var systems = result_file.System.length || 1;
		var results = result_file.Result.length || 1;
		var system_date = result_file.System.TimeStamp || result_file.System[(systems - 1)].TimeStamp;
		var system_date_obj = new Date(system_date);

		document.getElementById("result_" + b64id(result)).innerHTML = "<strong>" + result_file.Generated.Title + "</strong><br /><span style=\"font-size: 10px;\">" + system_date_obj.toLocaleDateString() + " - " + plural_handler(systems, "System") + " " + plural_handler(results, "Result") + "</span>";
	} else alert(result);
}
function update_system_log_viewer(j)
{
	var option = document.createElement("option");
	option.text = "System Log Viewer";
	option.value = "";
	document.getElementById("log_viewer_selector").appendChild(option);

	for(var i = 0; i < j.pts.msg.logs.length; i++)
	{
		option = document.createElement("option");
		option.text = j.pts.msg.logs[i];
		option.value = j.pts.msg.logs[i];
		document.getElementById("log_viewer_selector").appendChild(option);
	}
}
function test_add_to_queue(f, ids, tp_id, tp)
{
	var option_title;
	var option_name;
	var option_value;
	var options = new Array();
	if(f.length > 0)
	{
		var identifiers = ids.split(':');
		for(var i = 0; i < identifiers.length; i++)
		{
			option_title = document.getElementById(f + identifiers[i] + "_title").value;
			var el = document.getElementById(f + identifiers[i]);

			if(el.tagName == 'INPUT')
			{
				option_name = el.value;
				option_value = el.value;
			}
			else if(el.tagName == 'SELECT')
			{
				option_name = el.options[el.selectedIndex].innerHTML;
				option_value = el.options[el.selectedIndex].value;
			}

			var opt = new Object();
			opt.title = option_title;
			opt.name = option_name;
			opt.value = option_value;
			options.push(opt);
		}
	}

	var test = new Object();
	test.test_profile_id = tp_id;
	test.test_profile = JSON.parse(atob(tp));
	test.options = options;

	if(localStorage.test_queue)
	{
		var tq = JSON.parse(localStorage.test_queue);
	}
	else
	{
		var tq = new Array();
	}

	tq.push(test);
	localStorage.test_queue = JSON.stringify(tq);
	document.getElementById('pts_add_test_area').innerHTML = "<p style=\"text-align: center; font-weight: bold;\">This test has been added to the next benchmark queue.</p>";
	update_benchmark_button();
}
function get_test_queue()
{
	var tq = new Array();

	if(localStorage.test_queue)
	{
		var tq = JSON.parse(localStorage.test_queue);
	}

	return tq;
}
function send_benchmark_request(base64_json_string)
{
	if(document.getElementById("pts_test_name").value == "")
	{
		alert("Please enter a test name.");
		return false;
	}
	if(document.getElementById("pts_test_identifier").value == "")
	{
		alert("Please enter a test identifier.");
		return false;
	}
	if(document.getElementById("pts_test_description").value == "")
	{
		alert("Please enter a test description.");
		return false;
	}

	var req = new Object();
	req.title = document.getElementById("pts_test_name").value;
	req.identifier = document.getElementById("pts_test_identifier").value;
	req.description = document.getElementById("pts_test_description").value;
	req.tests = JSON.parse(atob(base64_json_string));
	base64_json_string = btoa(JSON.stringify(req))
	localStorage.test_queue_submit = base64_json_string;
	window.location.href = '/?benchmark';
}
function send_benchmark_request_received(j)
{
	if(j.pts.msg.error && j.pts.msg.error.length > 0)
	{
		alert(j.pts.msg.error);
	}
	else
	{
		window.location.href = '/?benchmark';
	}
}
function get_test_options_value(options)
{
	var title = "";
	for(var i = 0; i < options.length; i++)
	{
		if(options[i].title == "")
		{
			continue;
		}
		if(title != "")
		{
			title += " ";
		}
		title += options[i].value;
	}

	return title;
}
function get_test_options_title(options)
{
	var title = "";
	for(var i = 0; i < options.length; i++)
	{
		if(options[i].title == "")
		{
			continue;
		}
		if(title != "")
		{
			title += " - ";
		}
		title += options[i].title + ": " + options[i].name;
	}

	return title;
}
function log_viewer_change()
{
	var log_view = document.getElementById("log_viewer_selector").options[document.getElementById("log_viewer_selector").selectedIndex].value;
	if(log_view.length > 0)
	{
		pts_web_socket.add_onmessage_event("fetch_system_log", "update_system_log_view");
		pts_web_socket.send("fetch-system-log " + log_view);
	}
}
function update_system_log_view(j)
{
	var system_log = atob(j.pts.msg.log);
	system_log = system_log.replace("\n", "<br />");
	document.getElementById("system_log_display").style.display = "block";
	document.getElementById("system_log_display").innerHTML = "<h2>" + j.pts.msg.log_name + "</h2><pre>" + system_log + "</pre>";
}
function update_svg_graph_space(jsonr)
{
	document.getElementById("svg_graphs").innerHTML = jsonr.pts.msg.contents;
}
function update_large_svg_graph_space(jsonr)
{
	document.getElementById("large_svg_graphs").innerHTML = atob(jsonr.pts.msg.contents);
}
function tests_by_popularity_display(j)
{
	if(j.pts.msg.test_type == null)
	{
		j.pts.msg.test_type = "";
	}

	document.getElementById("tests_by_popularity").innerHTML = "<h2>Most Popular " +  j.pts.msg.test_type + " Tests</h2>";

	for(var i = 0; i < j.pts.msg.tests.length; i++)
	{
		var test = j.pts.msg.tests[i];
		var test_profile = JSON.parse(atob(j.pts.msg.test_profiles[i]));

		document.getElementById("tests_by_popularity").innerHTML += "<h3><a href=\"?test/" + test + "\">" + test_profile.TestInformation.Title + "</a></h3>";
		var test_description = test_profile.TestInformation.Description;
		document.getElementById("tests_by_popularity").innerHTML += "<p>" + test_description + "</p>";
	}
	document.getElementById("tests_by_popularity").innerHTML += "<p><a href=\"\?tests#" + j.pts.msg.test_type + "\">More " + j.pts.msg.test_type + " Tests</a></p>";
}
function update_benchmark_button()
{
	if(localStorage.test_queue)
	{
		var test_queue = JSON.parse(localStorage.test_queue);
		document.getElementById('pts_benchmark_button_area').innerHTML = '<a href="/?test_queue"><div id="pts_benchmark_button">' +  plural_handler(test_queue.length, 'Test') + ' Queued To Benchmark</div></a>';
	}
}
function pts_color_rotate(eid)
{
	eid.style.stroke = "#000";
}
function pts_number_to_string(num)
{
	if(num < 11)
	{
		var numstrings = new Array("One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten");
		num = numstrings[(num - 1)];
	}

	return num;
}
function pts_seconds_to_pretty_string(secs)
{
	var pretty = "";

	if(secs < 60)
	{
		pretty = plural_handler(secs, "second");
	}
	else if(secs < 180)
	{
		pretty = plural_handler(Math.floor(secs / 60), "minute") + ", " + plural_handler((secs % 60), "second");
	}
	else
	{
		pretty = Math.ceil(secs / 60) + " minutes";
	}

	return pretty;
}
function pts_set_completion_circle(percent_complete, sub_text, el)
{
	var size = 200;
	var radius = size / 2;
	var stroke_width = 20;
	var center = radius + stroke_width;

	if(percent_complete < 100)
	{
		percent_complete = percent_complete.toPrecision(2);
		var deg = (percent_complete / 100) * 360;
		var offset_deg = 1 - deg;
		var arc = percent_complete > 50 && percent_complete < 100 ? 1 : 0;

		var p1_x = Math.round(Math.cos((offset_deg * (Math.PI / 180))) * radius) + center;
		var p1_y = Math.round(Math.sin((offset_deg * (Math.PI / 180))) * radius) + center;
		var p2_x = Math.round(Math.cos(((offset_deg + deg) * (Math.PI / 180))) * radius) + center;
		var p2_y = Math.round(Math.sin(((offset_deg + deg) * (Math.PI / 180))) * radius) + center;
	}

	var output = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewbox="0 0 ' + (center * 2) + ' ' + (center * 2) + '" style="min-height: 100px; max-height: ' + (window_size.height * 0.4) + 'px; display: block; text-align: center; margin: 5px auto;">';
	output += '<circle cx="' + center + '" cy="' + center + '" r="' + radius + '" onload="javascript:pts_color_rotate(this);" stroke="#044374" stroke-width="' + (stroke_width / 2) + '" fill="#FFF" />';
	if(percent_complete >= 100)
	{
		output += '<circle cx="' + center + '" cy="' + center + '" r="' + radius + '" stroke="#dd4b39" stroke-width="' + stroke_width + '" fill="#FFF" />';
	}
	else if(percent_complete > 0)
	{
		output += '<path d="M' + center + ',' + center + ' L' + p1_x + ',' + p1_y + 'A' + radius + ',' + radius + ' 0 ' + arc + ',1 ' + p2_x  + ',' + p2_y + ' Z" fill="#FFF" stroke="#dd4b39" stroke-width="' + stroke_width + '" />';
	}
	output += '<circle cx="' + center + '" cy="' + center + '" r="' + (radius - (stroke_width / 2)) + '" fill="#FFF" stroke-width="0" />';
	output += '<text x="' + center + '" y="' + center + '" font-size="20" font-weight="bold" fill="#044374" text-anchor="middle" alignment-baseline="middle" xlink:show="new">' + percent_complete + '% Complete</text>';

	if(sub_text.length > 0)
	{
		output += '<text x="' + center + '" y="' + (center * 1.25) + '" font-size="15" font-weight="bold" fill="#BABABA" text-anchor="middle" alignment-baseline="middle" xlink:show="new">' + sub_text + '</text>';
	}
	output += '</svg>';

	if(document.getElementById(el))
	{
		document.getElementById(el).innerHTML = output;
	}
	else
	{
		document.write(output);
	}
}
function pts_set_completion_circle_array(percent_complete_r, sub_text_r, el)
{
	var size = 300;
	var stroke_width = 10;
	var center = size / 2;
	var color_table = new Array("#044374", "#dd4b39", '#BABABA');

	var output = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewbox="0 0 ' + (center * 2) + ' ' + (center * 2) + '" style="min-height: 100px; max-height: ' + (window_size.height * 0.6) + 'px; display: block; text-align: center; margin: 5px auto;">';
	for(var i = 0; i < percent_complete_r.length; i++)
	{
		var percent_complete = percent_complete_r[i];
		var radius = (size - ((i + 1) * stroke_width * 2.5)) / 2;

		if(percent_complete < 100)
		{
			percent_complete = percent_complete.toPrecision(2);
			var deg = (percent_complete / 100) * 360;
			var offset_deg = 1 - deg;
			var arc = percent_complete > 50 && percent_complete < 100 ? 1 : 0;

			var p1_x = Math.round(Math.cos((offset_deg * (Math.PI / 180))) * radius) + center;
			var p1_y = Math.round(Math.sin((offset_deg * (Math.PI / 180))) * radius) + center;
			var p2_x = Math.round(Math.cos(((offset_deg + deg) * (Math.PI / 180))) * radius) + center;
			var p2_y = Math.round(Math.sin(((offset_deg + deg) * (Math.PI / 180))) * radius) + center;
		}

		output += '<circle cx="' + center + '" cy="' + center + '" r="' + radius + '" onload="javascript:pts_color_rotate(this);" stroke="#FFFFFF" stroke-width="' + (stroke_width / 4) + '" fill="#FFFFFF" />';

		if(percent_complete >= 100)
		{
			output += '<circle cx="' + center + '" cy="' + center + '" r="' + radius + '" stroke="' + color_table[(i % color_table.length)] + '" stroke-width="' + stroke_width + '" fill="#FFF" />';
		}
		else if(percent_complete > 0)
		{
			output += '<path d="M' + center + ',' + center + ' L' + p1_x + ',' + p1_y + 'A' + radius + ',' + radius + ' 0 ' + arc + ',1 ' + p2_x  + ',' + p2_y + ' Z" fill="#FFF" stroke="' + color_table[(i % color_table.length)] + '" stroke-width="' + stroke_width + '" />';
		}
		output += '<circle cx="' + center + '" cy="' + center + '" r="' + (radius - (stroke_width / 2)) + '" fill="#FFF" stroke-width="0" />';
	}

	output += '<text x="' + center + '" y="' + (center / (sub_text_r.length + 1)) * 2 + '" font-size="20" font-weight="bold" fill="#044374" text-anchor="middle" alignment-baseline="middle" xlink:show="new">' + percent_complete_r[0].toPrecision(2) + '% Complete</text>';

	for(i = 0; i < sub_text_r.length; i++)
	{
		output += '<text x="' + center + '" y="' + (center / (sub_text_r.length + 1) * (i + 3)) + '" font-size="15" font-weight="bold" fill="#BABABA" text-anchor="middle" alignment-baseline="middle" xlink:show="new">' + sub_text_r[i] + '</text>';
	}

	output += '</svg>';

	if(document.getElementById(el))
	{
		document.getElementById(el).innerHTML = output;
	}
	else
	{
		document.write(output);
	}
}
