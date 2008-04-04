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
