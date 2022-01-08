<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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
//error_reporting(E_ALL | E_NOTICE | E_STRICT);

$json = array();

if(empty($BENCHMARK_TICKET_ID))
{
	$json['phoromatic']['error'] = 'No Ticket ID';
	echo json_encode($json);
	return false;
}

pts_file_io::mkdir(phoromatic_server::phoromatic_account_stress_log_path(ACCOUNT_ID));
$log_directory = phoromatic_server::phoromatic_account_stress_log_path(ACCOUNT_ID, $BENCHMARK_TICKET_ID);
pts_file_io::mkdir($log_directory);

if($LOGS != null)
{
	file_put_contents($log_directory . SYSTEM_ID . '.log', pts_strings::sanitize($LOGS));
	$json['phoromatic']['response'] = 'Log Updated';
	echo json_encode($json);
	return true;
}

$json['phoromatic']['error'] = 'End Termination Error';
echo json_encode($json);
return false;

?>
