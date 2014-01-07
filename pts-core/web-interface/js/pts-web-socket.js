function pts_web_socket()
{
	this.socket = false;
	this.socket_path = "main";
	this.socket_connected = 0;
	this.socket_onopen = new Array();
	this.socket_onmsg = new Array();
	this.socket_onclose = new Array();

	this.set_web_socket_path = function(p)
	{
		this.socket_path = p;
	}
	this.add_onmessage_event = function(name, func)
	{
		var e = new Object();
		e.msg_name = name;
		e.msg_function = func;
		this.socket_onmsg.push(e);
	}
	this.add_onopen_event = function(msg)
	{
		this.socket_onopen.push(msg);
	}
	this.add_onclose_event = function(func)
	{
		this.socket_onclose.push(func);
	}
	this.is_connected = function()
	{
		return this.socket_connected == 1;
	}
	this.connect = function()
	{
		this.socket = new WebSocket(pts_read_cookie("pts_websocket_server") + this.socket_path);
		this.socket.onopen    = function() { setTimeout(function(){pts_web_socket.web_socket_onopen(); }, 100); };
		this.socket.onmessage = function(msg) { pts_web_socket.web_socket_onmessage(msg); } ;
		this.socket.onclose   = function() { pts_web_socket.web_socket_onclose(); };
		this.socket.onerror   = function() { setTimeout(function() { pts_web_socket.web_socket_connect(); }, 4000); };
		return true;
	}
	this.web_socket_onopen = function()
	{
		this.socket_connected = 1;
		for(var i = 0; i < this.socket_onopen.length; i++)
		{
			this.send(this.socket_onopen[i]);
		}
	}
	this.web_socket_onclose = function()
	{
		this.socket_connected = 0;

		for(var i = 0; i < this.socket_onclose.length; i++)
		{
			window[this.socket_onclose[i]]();
		}
	}
	this.send = function(msg)
	{
		if(this.is_connected())
		{
			this.socket.send(msg);
		}
		else
		{
			pts_web_socket.add_onopen_event(msg);
		}
	}
	this.web_socket_onmessage = function(msg)
	{
		var json_response = JSON.parse(msg.data);

		for(var i = 0; i < this.socket_onmsg.length; i++)
		{
			if(this.socket_onmsg[i].msg_name == json_response.pts.msg.name && this.socket_onmsg[i].msg_function)
			{
				window[this.socket_onmsg[i].msg_function](json_response);
			}
		}
	}
}
