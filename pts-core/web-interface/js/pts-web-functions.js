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

		if(j.pts.msg.tests.length == 0 && j.pts.msg.results.length > 0)
		{
			document.getElementById("search_results").innerHTML += "<p><strong>Test results referencing " + j.pts.msg.search_query + ".</strong></p>";
		}

		for(var i = 0; i < j.pts.msg.results.length; i++)
		{
			var result_file = JSON.parse(atob(j.pts.msg.result_files[i]));
			document.getElementById("search_results").innerHTML += "<h2><a href=\"?result/" + j.pts.msg.results[i] + "\">" + result_file.Generated.Title + "</a></h2>";
			document.getElementById("search_results").innerHTML += "<p>" + result_file.Generated.Description + "</p>";
		}
	}
}
