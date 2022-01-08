<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2022, Phoronix Media
	Copyright (C) 2014 - 2022, Michael Larabel

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

class phoromatic_search implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Search';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	protected static function search_test_profiles($q)
	{
		$ret = null;
		foreach(pts_search::search_local_test_profiles($q) as $test)
		{
			$tp = new pts_test_profile($test);
			$ret .= '<h3>' . $tp->get_title() . '</h3><p>' . $tp->get_description() . '<br /><a href="http://openbenchmarking.org/test/' . $tp->get_identifier(false) . '">Learn More On OpenBenchmarking.org</a></p>';
		}

		return $ret;
	}
	protected static function search_local_test_suites($q)
	{
		$ret = null;
		$suite_dir = phoromatic_server::phoromatic_account_suite_path($_SESSION['AccountID']);
		foreach(pts_file_io::glob($suite_dir . '*/suite-definition.xml') as $xml_path)
		{
			$id = basename(dirname($xml_path));
			$test_suite = new pts_test_suite($xml_path);
			$match = false;

			if(stripos($test_suite->get_title(), $q) === 0 || stripos($test_suite->get_description(), $q) !== false)
			{
				$match = true;
			}
			else
			{
				foreach($test_suite->get_contained_test_result_objects() as $tro)
				{
					if(stripos($tro->test_profile->get_identifier(), $q) !== false || stripos($tro->test_profile->get_title(), $q) === 0)
					{
						$match = true;
					}
				}
			}

			if($match)
			{
				$ret .= '<h3>' . $test_suite->get_title() . '</h3><p>' . $test_suite->get_description() . '<br /><a href="/?local_suites#' . $id . '">More Details</a></p>';
			}
		}

		return $ret;
	}
	protected static function search_test_schedules($q)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Title, Description, ScheduleID FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$ret = null;

		while($row = $result->fetchArray())
		{
			$match = false;
			if(stripos($row['Title'], $q) === 0 || stripos($row['Description'], $q) !== false)
			{
				$match = true;
			}
			else
			{
				$stmt2 = phoromatic_server::$db->prepare('SELECT TestProfile FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
				$stmt2->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt2->bindValue(':schedule_id', $row['ScheduleID']);
				$result2 = $stmt2->execute();
				while($row2 = $result2->fetchArray())
				{
					if(stripos($row2['TestProfile'], $q) !== false)
					{
						$match = true;
					}
				}
			}

			if($match)
			{
				$ret .= '<h3>' . $row['Title'] . '</h3><p>' . $row['Description'] . '<br /><a href="/?schedules/' . $row['ScheduleID'] . '">More Details</a></p>';
			}
		}

		return $ret;
	}
	protected static function search_test_results($q)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Title, Description, UploadID, PPRID FROM phoromatic_results WHERE AccountID = :account_id');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$ret = null;

		while($row = $result->fetchArray())
		{
			$match = false;
			if(stripos($row['Title'], $q) === 0 || stripos($row['Description'], $q) !== false)
			{
				$match = true;
			}
			else
			{
				$stmt2 = phoromatic_server::$db->prepare('SELECT TestProfile FROM phoromatic_results_results WHERE AccountID = :account_id AND UploadID = :upload_id');
				$stmt2->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt2->bindValue(':upload_id', $row['UploadID']);
				$result2 = $stmt2->execute();
				while($row2 = $result2->fetchArray())
				{
					if(stripos($row2['TestProfile'], $q) !== false)
					{
						$match = true;
					}
				}
			}

			if($match)
			{
				$ret .= '<h3>' . $row['Title'] . '</h3><p>' . $row['Description'] . '<br /><a href="/?result/' . $row['PPRID'] . '">View Results</a></p>';
			}
		}

		return $ret;
	}
	protected static function search_test_systems($q)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Title, Description, SystemID, Hardware, Software FROM phoromatic_systems WHERE AccountID = :account_id');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$ret = null;

		while($row = $result->fetchArray())
		{
			$match = false;
			if(stripos($row['Title'], $q) === 0 || stripos($row['Description'], $q) !== false || stripos($row['Hardware'], $q) !== false || stripos($row['Software'], $q) !== false)
			{
				$match = true;
			}

			if($match)
			{
				$ret .= '<h3>' . $row['Title'] . '</h3><p>' . $row['Description'] . '<br /><a href="/?systems/' . $row['SystemID'] . '">View System</a></p>';
			}
		}

		return $ret;
	}
	public static function render_page_process($PATH)
	{
		$search_query = pts_strings::sanitize($_REQUEST['search']);
		$main = null;

		if(strlen($search_query) < 4)
		{
			$main = '<h1>Search Failed</h1>';
			$main .= '<p>Search Queries Must Be At Least Four Characters.</p>';
		}
		else
		{
			$main .= '<h1>Search Results For: ' . $search_query . '</h1>';
			$category_matches = 0;

			$tests = self::search_test_profiles($search_query);
			if($tests != null)
			{
				$category_matches++;
				$main .= '<h2>Test Profile Matches</h2>' . $tests . '<hr />';
			}

			$local_suites = self::search_local_test_suites($search_query);
			if($local_suites != null)
			{
				$category_matches++;
				$main .= '<h2>Local Test Suite Matches</h2>' . $local_suites . '<hr />';
			}

			$test_schedules = self::search_test_schedules($search_query);
			if($test_schedules != null)
			{
				$category_matches++;
				$main .= '<h2>Test Schedule Matches</h2>' . $test_schedules . '<hr />';
			}

			$test_results = self::search_test_results($search_query);
			if($test_results != null)
			{
				$category_matches++;
				$main .= '<h2>Test Result Matches</h2>' . $test_results . '<hr />';
			}

			$test_systems = self::search_test_systems($search_query);
			if($test_systems != null)
			{
				$category_matches++;
				$main .= '<h2>Test System Matches</h2>' . $test_systems . '<hr />';
			}

			if($category_matches == 0)
			{
				$main .= '<h2>No Matches Found</h2>';
			}
		}

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
