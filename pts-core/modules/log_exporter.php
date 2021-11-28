<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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

class log_exporter extends pts_module_interface
{
	const module_name = 'Log Exporter';
	const module_version = '1.0.0';
	const module_description = 'This module allows for easily exporting test run logs and system logs to external locations via specifying the directory paths via the COPY_TEST_RUN_LOGS_TO and COPY_SYSTEM_LOGS_TO environment variables.';
	const module_author = 'Michael Larabel';

	public static function module_environment_variables()
	{
		return array('COPY_TEST_RUN_LOGS_TO', 'COPY_SYSTEM_LOGS_TO');
	}
	public static function __test_log_output($log_file_path)
	{
		if(getenv('COPY_TEST_RUN_LOGS_TO') == null)
		{
			return;
		}
		$COPY_TEST_RUN_LOGS_TO = getenv('COPY_TEST_RUN_LOGS_TO');

		pts_file_io::mkdir($COPY_TEST_RUN_LOGS_TO);
		if(is_writable($COPY_TEST_RUN_LOGS_TO))
		{
			copy($log_file_path, $COPY_TEST_RUN_LOGS_TO . '/' . basename($log_file_path));
		}
	}
	public static function __post_test_run_system_logs($log_file_path)
	{
		if(getenv('COPY_SYSTEM_LOGS_TO') == null)
		{
			return;
		}
		$COPY_SYSTEM_LOGS_TO = getenv('COPY_SYSTEM_LOGS_TO');

		pts_file_io::mkdir($COPY_SYSTEM_LOGS_TO);
		if(is_writable($COPY_SYSTEM_LOGS_TO))
		{
			foreach(pts_file_io::glob($log_file_path . '/*') as $sys_log_file)
			{
				copy($sys_log_file, $COPY_SYSTEM_LOGS_TO . '/' . basename($sys_log_file));
			}
		}
	}
}

?>
