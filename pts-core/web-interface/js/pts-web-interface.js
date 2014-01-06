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
function pts_logo()
{
	document.write('<svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" width="324" height="178" viewBox="0 0 323 178" id="pts_loading_logo" style="opacity: 1;" version="1.1"> <g id="layer" transform="translate(-213,-444)"> <g style="fill:#000000" id="g3316"> <path d="m 266.79146,488.83186 c 6.07132,9e-5 11.69056,1.16269 16.85774,3.48781 5.29623,2.19611 9.94664,5.29638 13.95123,9.30082 4.00444,3.87541 7.10471,8.46123 9.30082,13.75747 2.32512,5.16717 3.48772,10.78642 3.48781,16.85773 -9e-5,5.94223 -1.16269,11.56147 -3.48781,16.85774 -2.19611,5.29633 -5.29638,9.94673 -9.30082,13.95123 -4.00459,4.00453 -8.655,7.16939 -13.95123,9.49459 -5.16718,2.19603 -10.78642,3.29404 -16.85774,3.29404 l -26.15856,0 0,34.87808 -17.24527,0 0,-78.47568 c 0,-6.07131 1.09801,-11.69056 3.29404,-16.85773 2.3252,-5.29624 5.42547,-9.88206 9.30082,-13.75747 4.00451,-4.00444 8.65491,-7.10471 13.95123,-9.30082 5.29627,-2.32512 10.91551,-3.48772 16.85774,-3.48781 m 0,69.75616 c 3.61694,2e-5 7.04016,-0.64587 10.26966,-1.93767 3.22939,-1.42094 6.00672,-3.29402 8.33198,-5.61925 2.45432,-2.45435 4.3274,-5.29627 5.61925,-8.52575 1.42089,-3.22941 2.13137,-6.65263 2.13144,-10.26966 -7e-5,-3.61693 -0.71055,-6.97556 -2.13144,-10.07588 -1.29185,-3.2294 -3.16493,-6.00673 -5.61925,-8.33199 -2.32526,-2.45432 -5.10259,-4.3274 -8.33198,-5.61925 -3.2295,-1.42089 -6.65272,-2.13136 -10.26966,-2.13143 -3.61702,7e-5 -7.04024,0.71054 -10.26966,2.13143 -3.1003,1.29185 -5.87762,3.16493 -8.33198,5.61925 -2.32523,2.32526 -4.19831,5.10259 -5.61925,8.33199 -1.2918,3.10032 -1.93769,6.45895 -1.93767,10.07588 l 0,26.35233 26.15856,0 0,0" style="fill:#000000;fill-opacity:1;" id="pts_highlight_1" /> <path d="m 368.12918,558.20049 0,17.24527 -8.52575,0 c -5.94223,0 -11.56147,-1.09801 -16.85774,-3.29404 -5.29632,-2.3252 -9.94673,-5.49006 -13.95123,-9.49459 -4.00453,-4.0045 -7.16939,-8.65491 -9.49459,-13.95123 -2.19603,-5.29627 -3.29404,-10.91551 -3.29404,-16.85774 l 0,-78.28191 17.24527,0 0,52.31712 34.87808,0 0,17.24527 -34.87808,0 0,8.71952 c -2e-5,3.61703 0.64587,7.04024 1.93767,10.26966 1.42094,3.22948 3.29402,6.07139 5.61925,8.52575 2.45435,2.32523 5.29627,4.19831 8.52575,5.61925 3.22942,1.29179 6.65263,1.93768 10.26966,1.93767 l 8.52575,0" style="fill:#000000;" id="pts_highlight_2" /> <path d="m 452.56925,488.44433 0,17.24527 -52.51088,0 c -2.45441,7e-5 -4.52126,0.83973 -6.20055,2.51897 -1.67933,1.67938 -2.51899,3.74623 -2.51897,6.20055 -2e-5,2.45444 0.83964,4.58588 2.51897,6.39432 1.67929,1.67936 3.74614,2.51902 6.20055,2.51897 l 34.87808,0 c 3.61692,5e-5 6.97554,0.71053 10.07589,2.13144 3.22937,1.29183 6.0067,3.10032 8.33198,5.42548 2.4543,2.32524 4.32738,5.10257 5.61925,8.33198 1.42087,3.10031 2.13135,6.45893 2.13144,10.07589 -9e-5,3.61701 -0.71057,7.04022 -2.13144,10.26966 -1.29187,3.10028 -3.16495,5.87761 -5.61925,8.33198 -2.32528,2.32521 -5.10261,4.19829 -8.33198,5.61925 -3.10035,1.29178 -6.45897,1.93767 -10.07589,1.93767 l -52.31712,0 0,-17.24527 52.31712,0 c 2.58349,1e-5 4.71493,-0.83964 6.39431,-2.51898 1.67925,-1.67929 2.5189,-3.81072 2.51897,-6.39431 -7e-5,-2.45435 -0.83972,-4.5212 -2.51897,-6.20055 -1.67938,-1.67928 -3.81082,-2.51893 -6.39431,-2.51897 l -34.87808,0 c -3.61701,4e-5 -7.04023,-0.64585 -10.26966,-1.93767 -3.10029,-1.42092 -5.81302,-3.294 -8.13822,-5.61925 -2.32521,-2.45434 -4.19829,-5.23166 -5.61924,-8.33198 -1.29179,-3.2294 -1.93767,-6.65262 -1.93768,-10.26966 10e-6,-3.61692 0.64589,-6.97555 1.93768,-10.07589 1.42095,-3.22938 3.29403,-6.0067 5.61924,-8.33198 2.3252,-2.32513 5.03793,-4.13362 8.13822,-5.42548 3.22943,-1.42088 6.65265,-2.13135 10.26966,-2.13144 l 52.51088,0" style="fill:#000;" id="pts_highlight_3" /> </g> <rect x="467.20938" y="463.19574" width="17.688" height="113.794" id="pts_highlight_4" style="fill:#000" /> <rect x="488.65765" y="510.29648" width="17.688" height="66.424004" id="pts_highlight_5" style="fill:#000" /> <rect x="509.69238" y="539.93774" width="16.92" height="37.053001" id="pts_highlight_6" style="fill:#000" /> </g> </svg>');
}
