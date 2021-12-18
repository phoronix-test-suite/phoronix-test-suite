<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

if(isset($_POST['manifest']))
{
	$encoded_json = $_POST['manifest'];
	$J = json_decode($encoded_json, true);
	if(!empty($J))
	{
pts_file_io::mkdir(phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID));
		pts_file_io::mkdir(phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID, SYSTEM_ID));
		$system_path = phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID, SYSTEM_ID);
		file_put_contents($system_path . 'test-installations.json',$encoded_json);
	}
}
?>
