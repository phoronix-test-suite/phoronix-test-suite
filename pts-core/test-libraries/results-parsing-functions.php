<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

function pts_read_log_file()
{
	$log_file = getenv("LOG_FILE");

	return is_file($log_file) ? file_get_contents($log_file) : null;
}
function pts_report_numeric_result($result)
{
	if(is_numeric($result))
	{
		pts_report_result($result);
	}
}
function pts_report_line_graph_array($elements)
{
	if(is_array($elements))
	{
		pts_report_result(implode(",", $elements));
	}
}
function pts_report_result($result)
{
	// For now it's just a matter of printing the result
	echo $result;
}

include(getenv("PARSE_RESULTS_SCRIPT"));

?>
