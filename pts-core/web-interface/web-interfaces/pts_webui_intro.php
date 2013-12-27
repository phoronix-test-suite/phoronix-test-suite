<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013, Phoronix Media
	Copyright (C) 2013, Michael Larabel

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


class pts_webui_intro implements pts_webui_interface
{
	public static function page_title()
	{
		return null;
	}
	public static function page_header()
	{
		return 'Phoronix Test Suite';
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		echo '<h2>Welcome</h2>' . PHP_EOL;
		echo '<p>' . str_replace("\n", '<br />', file_get_contents(PTS_CORE_STATIC_PATH . 'short-description.txt')) . '</p>';
		echo '<h2>User Agreement</h2>';
		echo '<p>' . str_replace("\n", '<br />', file_get_contents(PTS_CORE_PATH . 'user-agreement.txt')) . '</p>';
		echo '<form name="agreement" action="?agreement.php" method="post">';
		echo '<p align="center"> <input type="submit" value="Start Benchmarking"></p></form>';
	}
}

?>
