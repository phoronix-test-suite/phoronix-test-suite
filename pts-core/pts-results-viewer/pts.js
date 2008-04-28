function switchShow(i)
{
	if(document.getElementById(i).style.display == "none")
		showObject(i);
	else
		hideObject(i);
}
function hideObject(i)
{
	document.getElementById(i).style.display = "none";
}
function showObject(i)
{
	document.getElementById(i).style.display = "block";
}
function setImagesFromURL()
{
	var split;
	var html = "";
	var segment;
	var h = 0;
	var pf = location.href;
	pf = pf.substring(pf.search(/#/) + 1);

	do
	{
		split = pf.search(/,/);
		segment = pf.substring(0, split);
		pf = pf.substring(split + 1);

		if(segment.length > 0)
			html += "<p align=\"center\"><img src=\"" + segment + "\" /></p>";

		if(h == 1)
			h = 2;
		else if(pf.search(/,/) == -1)
			h = 1;
	}
	while(h != 2);

	document.getElementById("pts_monitor").innerHTML = html;
}
