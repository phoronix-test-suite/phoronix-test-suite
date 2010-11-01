
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
	var html = "";
	var pf = location.href;
	pf = pf.substring(pf.search(/#/) + 1);
	var imgarray = pf.split(",");

	for(i = 0; i < imgarray.length; i++)
	{
		var exsplit = imgarray[i].split(".");

		if(exsplit[1] == "svg")
			html += "<p align=\"center\"><object type=\"image/svg+xml\" data=\"" + imgarray[i] + "\" /></p>";
		else
			html += "<p align=\"center\"><img src=\"" + imgarray[i] + "\" /></p>";
	}

	document.getElementById("pts_monitor").innerHTML = html;
}
function boldBodyComponents()
{
	var el = document.getElementsByName('pts_column_body');

	for(var i = 0; i < el.length; i++)
	{
		el[i].innerHTML = el[i].innerHTML.replace(/:/, ":</strong>");
		el[i].innerHTML = el[i].innerHTML.replace(/,/, ", <strong>");
		el[i].innerHTML = "<strong>" + el[i].innerHTML;
	}
}
