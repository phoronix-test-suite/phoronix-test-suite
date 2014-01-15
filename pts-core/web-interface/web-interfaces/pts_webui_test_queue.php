<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2013, Michael Larabel

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


class pts_webui_test_queue implements pts_webui_interface
{
	private static $test_profile = false;

	public static function preload($REQUEST)
	{
		return true;
	}
	public static function page_title()
	{
		return 'Test Queue To Run';
	}
	public static function page_header()
	{
		return null;
	}
	public static function render_page_process($PATH)
	{
		echo '

		<h1>Tests To Run</h1>
		<script type="text/javascript">
			var tq = get_test_queue();

		for(var i = 0; i < tq.length; i++)
		{
			tq[i].test_profile = JSON.parse(tq[i].test_profile);
			document.write("<h2><a href=\"/?test/" + tq[i].test_profile_id + "\">" + tq[i].test_profile.TestInformation.Title + "</a></h2>");
			document.write("<p>" + get_test_options_title(tq[i].options) + "</p>");
		}

		</script>
	';

	}
}

?>
