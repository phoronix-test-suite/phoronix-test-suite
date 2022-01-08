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
//error_reporting(E_ALL | E_NOTICE | E_STRICT);

$json = array();

$upload_id = $ID; // i parameter

$result_directory = phoromatic_server::phoromatic_account_result_path(ACCOUNT_ID, $upload_id);
if(is_file($result_directory . 'composite.xml'))
{
	$system_logs_types = array('system-logs', 'installation-logs', 'test-logs');

	// Allow uploading zips assuming the desired type matches, no current zip file exists for given result file
	// TODO maybe add further check like that the UploadID just stems from the past day or so?

	foreach($system_logs_types as $possible_type)
	{
		if($possible_type == $SYSTEM_LOGS_TYPE)
		{
			if(is_file($result_directory . $possible_type . '.zip'))
			{
				$json['phoromatic']['error'] = 'Log Archive Already Exists';
			}
			else if($SYSTEM_LOGS_ZIP != null && $SYSTEM_LOGS_HASH != null)
			{
				if(sha1($SYSTEM_LOGS_ZIP) == $SYSTEM_LOGS_HASH && !empty($_POST['system_logs_zip']))
				{
					$system_logs_zip = $result_directory . $possible_type . '.zip';
					file_put_contents($system_logs_zip, base64_decode($_POST['system_logs_zip']));
	
					unset($SYSTEM_LOGS_ZIP);
			
					$json['phoromatic']['upload_id'] = $upload_id;
					$json['phoromatic']['uploaded'] = 1;
				}
				else
				{
					$json['phoromatic']['error'] = 'Log Upload Failed Due To Hash';
				}
			}
			break;
		}
	}
}
else
{
	$json['phoromatic']['error'] = 'No Matching Upload ID Found';
}

if(empty($json))
{
	$json['phoromatic']['error'] = 'Log Upload Failed';
}
echo json_encode($json);
return false;

?>
