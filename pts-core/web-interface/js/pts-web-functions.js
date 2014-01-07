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
function test_add_to_queue(f, ids, tp)
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
	test.test_profile = tp;
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
		document.getElementById('pts_benchmark_button_area').innerHTML = '<a href=""><div id="pts_benchmark_button">' +  plural_handler(test_queue.length, 'Test') + ' Queued To Benchmark</div></a>';
	}
}
