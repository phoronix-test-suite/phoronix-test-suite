function pts_add_web_notification(title, message)
{
	var new_row = document.createElement('tr');
	var tr_id = 'notify_row_' +  Math.floor((Math.random()*10000)+1);
	new_row.setAttribute('id', tr_id);
	new_row.setAttribute('onclick', 'javascript:document.getElementById(\'' + tr_id + '\').style.display = \'none\';');

	var new_message = document.createElement('td');
	new_message.innerHTML = '<h5>' + title + '</h5><p>' + message + '</p><a onclick="javascript:document.getElementById(\'' + tr_id + '\').style.display = \'none\';">Close</a>';

	new_row.appendChild(new_message);
	document.getElementById('notification_area').appendChild(new_row);
}
function pts_fade_in(id, rate)
{
	var opacity = 0.01;
	var timer = setInterval(function ()
	{
		if(opacity >= 1)
		{
			clearInterval(timer);
			document.getElementById(id).style.opacity = 1.0;
		}
		document.getElementById(id).style.opacity = opacity;
		opacity *= (1 + rate);
	}, 50);
}
function pts_fade_out(id, rate)
{
	var opacity = 1;
	var timer = setInterval(function ()
	{
		if(opacity <= 0)
		{
			clearInterval(timer);
			document.getElementById(id).style.opacity = 0;
			document.getElementById(id).style.display = 'none';
		}
		document.getElementById(id).style.opacity = opacity;
		opacity = opacity * rate;
	}, 50);
}
function pts_ajax_query(request, destination)
{
	var http = new Array();
	var rnow = new Date();
	http[rnow] = new XMLHttpRequest();
	http[rnow].open("GET", request, true);
	http[rnow].onreadystatechange = function(){
		if(http[rnow].readyState == 4)
		{
			if(http[rnow].status == 200 || http[rnow].status == 304)
			{
				document.getElementById(destination).innerHTML = http[rnow].responseText;
			}
		}}
	http[rnow].send(null);
	pollTimer = setInterval(handleResponse, 1000);
}
function pts_read_cookie(cookie_name)
{
	var cookie_name = cookie_name + "=";
	var cookies = document.cookie.split(';');
	for(var i = 0; i < cookies.length; i++)
	{
		var c = cookies[i].trim();
		if(c.indexOf(cookie_name) == 0)
		{
			return decodeURIComponent(c.substring(cookie_name.length, c.length));
		}
	}
	return;
}

var pts_highlighter_selection = 6;
function pts_highlight_loader_switch_color()
{
	var el = document.getElementById('pts_highlight_' + pts_highlighter_selection);
	if(el)
	{
		el.style.fill = '#000';
		pts_highlighter_selection++;
		if(pts_highlighter_selection == 7)
			pts_highlighter_selection = 1;
		document.getElementById('pts_highlight_' + pts_highlighter_selection).style.fill = '#949494';
	}
}
