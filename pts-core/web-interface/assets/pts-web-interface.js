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
function pts_fade_in(id)
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
		opacity *= 1.04;
	}, 50);
}
function pts_fade_out(id)
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
		opacity = opacity * 0.94;
	}, 50);
}
function pts_server_sent_event(display_id, request_address)
{
	if(typeof(EventSource) !== "undefined")
	{
		var sse = new EventSource(request_address);
		sse.onmessage = function(event)
			{
				document.getElementById(display_id).innerHTML = event.data;
			};
	}
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
