<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

$J = json_decode($JSON, true);
if($J == null)
{
	$json['phoromatic']['response'] = 'Failed';
	echo json_encode($json);
	exit;
}

pts_file_io::mkdir(phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID));
pts_file_io::mkdir(phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID, SYSTEM_ID));

$system_path = phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID, SYSTEM_ID);

if(isset($J['phoromatic']['client-log']))
{
	file_put_contents($system_path . 'phoronix-test-suite.log', $J['phoromatic']['client-log']);
}
if(isset($J['phoromatic']['stats']))
{
	file_put_contents($system_path . 'sensors.json', json_encode($J['phoromatic']['stats']));
}

$json['phoromatic']['response'] = 'tick';
$json['phoromatic']['tick_thread'] = '';
echo json_encode($json);
exit;

?>
