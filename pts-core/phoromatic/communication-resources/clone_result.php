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

$result_share_opt = phoromatic_server::read_setting('force_result_sharing') ? '1 = 1' : 'AccountID IN (SELECT AccountID FROM phoromatic_account_settings WHERE LetOtherGroupsViewResults = "1")';
$stmt = phoromatic_server::$db->prepare('SELECT AccountID, UploadID FROM phoromatic_results WHERE (' . $result_share_opt . ' OR AccountID = :account_id) AND PPRID = :pprid LIMIT 1');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':pprid', $ID);
$result = $stmt->execute();

if($result && ($row = $result->fetchArray()))
{
	$composite_xml = phoromatic_server::phoromatic_account_result_path($row['AccountID'], $row['UploadID']) . 'composite.xml';
	if($composite_xml)
	{
		$json['phoromatic']['result']['composite_xml'] = base64_encode($composite_xml);
		//$json['phoromatic']['result']['system_logs_available'] = is_file($sys_log_file) ? sha1_file($sys_log_file) : false;
		echo json_encode($json);
		return;
	}
}

?>
