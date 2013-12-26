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
