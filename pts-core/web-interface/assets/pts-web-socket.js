var pts_socket;
var pts_socket_path = "main";
var pts_socket_connected = 0;
var pts_socket_onopen = new Array();
var pts_socket_onmsg = new Array();
var pts_socket_onclose = new Array();

function pts_set_web_socket_path(p)
{
	pts_socket_path = p;
}
function pts_add_onmessage_event(name, func)
{
	var e = new Object();
	e.msg_name = name;
	e.msg_function = func;
	pts_socket_onmsg.push(e);
}
function pts_add_onopen_event(msg)
{
	pts_socket_onopen.push(msg);
}
function pts_add_onclose_event(func)
{
	pts_socket_onclose.push(func);
}
function pts_web_socket_connected()
{
	return pts_socket_connected;
}
function pts_web_socket_connect()
{
	pts_socket = new WebSocket(pts_read_cookie("pts_websocket_server") + pts_socket_path);
	pts_socket.onopen    = function() { pts_web_socket_onopen(); };
	pts_socket.onmessage = function(msg) { pts_web_socket_onmessage(msg); } ;
	pts_socket.onclose   = function() { pts_web_socket_onclose(); };
	pts_socket.onerror   = function() { setTimeout(function() { pts_web_socket_connect(); return false; }, 4000); };
	return true;
}
function pts_web_socket_onopen()
{
	pts_socket_connected = 1;
	for(var i = 0; i < pts_socket_onopen.length; i++)
	{
		pts_web_socket_send(pts_socket_onopen[i]);
	}
}
function pts_web_socket_onclose()
{
	pts_socket_connected = 0;

	for(var i = 0; i < pts_socket_onclose.length; i++)
	{
		window[pts_socket_onclose[i]]();
	}
}
function pts_web_socket_send(msg)
{
	if(pts_socket_connected == 1)
	{
		pts_socket.send(msg);
	}
}
function pts_web_socket_onmessage(msg)
{
	var json_response = JSON.parse(msg.data);

	for(var i = 0; i < pts_socket_onmsg.length; i++)
	{
		if(pts_socket_onmsg[i].msg_name == json_response.pts.element.name)
		{
			window[pts_socket_onmsg[i].msg_function](json_response);
		}
	}
}
