function search_results(j)
{
	var logo = document.getElementById("pts_loading_logo");
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
			document.getElementById("search_results").innerHTML += "<h1>No Tests Found</h1>";
		}

		for(var i = 0; i < j.pts.msg.tests.length; i++)
		{
			if(j.pts.msg.exact_hits == 0)
			{
				document.getElementById("search_results").innerHTML += "<p><em>There were no direct matches for &quot;" + j.pts.msg.search_query + "&quot;, but is mentioned in the following tests.</em></p>";
			}

			var test = j.pts.msg.tests[i];
			var test_profile = JSON.parse(atob(j.pts.msg.test_profiles[i]));
			document.getElementById("search_results").innerHTML += "<h2>" + test_profile.TestInformation.Title + "</h2>";

			var test_description = test_profile.TestInformation.Description;
			if(j.pts.msg.exact_hits == 0)
			{
				test_description = test_description.replace(new RegExp(j.pts.msg.search_query, 'g'), '<strong>' + j.pts.msg.search_query + '</strong>');
			}
			document.getElementById("search_results").innerHTML += "<p>" + test_description + "</p>";
			document.getElementById("search_results").innerHTML += "<p><a href=\"?test/" + test + "\">More " + test_profile.TestInformation.Title + " Information</a></p>";
		}
	}
}
