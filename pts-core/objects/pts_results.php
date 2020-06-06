<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2020, Phoronix Media
	Copyright (C) 2010 - 2020, Michael Larabel

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

class pts_results
{
	public static function remove_saved_result_file($identifier)
	{
		if(defined('PTS_SAVE_RESULTS_PATH') && is_dir(PTS_SAVE_RESULTS_PATH . $identifier) && strpos($identifier, '.') === false && strpos($identifier, '/') === false)
		{
			return pts_file_io::delete(PTS_SAVE_RESULTS_PATH . $identifier, null, true);
		}
		return false;
	}
	public static function saved_test_results_count()
	{
		return count(pts_file_io::glob(PTS_SAVE_RESULTS_PATH . '*/composite.xml'));
	}
	public static function saved_test_results()
	{
		$results = array();

		foreach(pts_file_io::glob(PTS_SAVE_RESULTS_PATH . '*/composite.xml') as $result_file)
		{
			$results[] = basename(dirname($result_file));
		}

		return $results;
	}
	public static function is_saved_result_file($identifier)
	{
		return defined('PTS_SAVE_RESULTS_PATH') && is_file(PTS_SAVE_RESULTS_PATH . $identifier . '/composite.xml');
	}
	public static function query_saved_result_files($search = null, $sort_by = null)
	{
		$result_files = array();
		if(empty($search))
		{
			$search = false;
		}
		if(empty($sort_by))
		{
			$search = false;
		}
		foreach(pts_results::saved_test_results() as $id)
		{
			$rf = new pts_result_file($id);

			if($search)
			{
				if(pts_search::search_in_result_file($rf, $search) == false)
				{
					continue;
				}
			}

			$result_files[$id] = $rf;
		}
		switch($sort_by)
		{
			case 'test_count':
				uasort($result_files, array('pts_results', 'sort_by_test_count'));
				break;
			case 'system_count':
				uasort($result_files, array('pts_results', 'sort_by_system_count'));
				break;
			case 'title':
				uasort($result_files, array('pts_results', 'sort_by_title'));
				break;
			case 'date':
			default:
				uasort($result_files, array('pts_results', 'sort_by_date'));
				break;
		}

		return $result_files;
	}
	protected static function sort_by_date($a, $b)
	{
		$a = strtotime($a->get_last_modified());
		$b = strtotime($b->get_last_modified());
		if($a == $b)
			return 0;
		return $a > $b ? -1 : 1;
	}
	protected static function sort_by_test_count($a, $b)
	{
		$a = $a->get_test_count();
		$b = $b->get_test_count();
		if($a == $b)
			return 0;
		return $a > $b ? -1 : 1;
	}
	protected static function sort_by_title($a, $b)
	{
		$a = $a->get_title();
		$b = $b->get_title();
		if($a == $b)
			return 0;
		return $a < $b ? -1 : 1;
	}
	protected static function sort_by_system_count($a, $b)
	{
		$a = $a->get_system_count();
		$b = $b->get_system_count();
		if($a == $b)
			return 0;
		return $a > $b ? -1 : 1;
	}
}

?>
