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
			$identifier = basename(dirname($result_file));
		}

		return $results;
	}
	public static function is_saved_result_file($identifier)
	{
		return is_file(PTS_SAVE_RESULTS_PATH . $identifier . '/composite.xml');
	}
}

?>
