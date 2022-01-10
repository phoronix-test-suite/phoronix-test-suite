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

include('../phoromatic_functions.php');
phoromatic_init_web_page_setup();
phoromatic_server::prepare_database();

$matching_user = phoromatic_server::$db->querySingle('SELECT AccountID, CreatedOn, UserName FROM phoromatic_users WHERE UserID = \'' . SQLite3::escapeString($_GET['user']) . '\' AND AdminLevel > 0', true);
if(!empty($matching_user))
{
	if(sha1($matching_user['CreatedOn']) == $_GET['v'])
	{
		$account_id = $matching_user['AccountID'];
		$user_name = $matching_user['UserName'];
	}
}

if(!isset($account_id) || empty($account_id) || !isset($user_name))
	exit;

header('Content-Type: text/xml; charset=utf-8');

echo '<?xml version="1.0"?>
<rss version="2.0">
 <channel>
	<title>Phoromatic - ' . $user_name . '</title>
	<link>' . $_SERVER['HTTP_HOST'] . '</link>
	<description>Phoronix Test Suite Phoromatic feed.</description>
	<language>en-us</language>';

$result_share_opt = phoromatic_server::read_setting('force_result_sharing') ? '1 = 1' : 'AccountID IN (SELECT AccountID FROM phoromatic_account_settings WHERE LetOtherGroupsViewResults = "1")';
$stmt = phoromatic_server::$db->prepare('SELECT Title, Description, PPRID, UploadTime FROM phoromatic_results  WHERE ' . $result_share_opt . ' OR AccountID = :account_id ORDER BY UploadTime DESC LIMIT 30');
$stmt->bindValue(':account_id', $account_id);
$test_result_result = $stmt->execute();
$results = 0;
while($row = $test_result_result->fetchArray())
{

	echo '  <item>' . PHP_EOL;
	echo '   <title>' . $row['Title'] . '</title>' . PHP_EOL;
	echo '   <link>http://' . $_SERVER['HTTP_HOST'] . '/?result/' . $row['PPRID'] . '</link>' . PHP_EOL;
	echo '   <guid>' . $row['PPRID'] . '</guid>' . PHP_EOL;
	echo '   <description>' . $row['Description'] . '</description>' . PHP_EOL;
	echo '   <pubDate>' . date('r', strtotime($row['UploadTime'])) . '</pubDate>' . PHP_EOL;
	echo '  </item>' . PHP_EOL;
}

echo ' </channel>
</rss>';

?>
