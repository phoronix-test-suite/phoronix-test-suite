<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2017, Phoronix Media
	Copyright (C) 2014 - 2017, Michael Larabel
	pts_logger.php: A simple log file generator

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

class pts_logger
{
	private $log_file = null;

	public function __construct($log_file = null, $file_name = null)
	{
		if($log_file == null)
		{
			$log_file = self::default_log_file_path();

			if($file_name != null)
			{
				$log_file .= $file_name;
			}
			else if(defined('PHOROMATIC_SERVER'))
			{
				$log_file .= 'phoromatic.log';
			}
			else
			{
				$log_file .= 'phoronix-test-suite.log';
			}
		}

	//	if(file_exists($log_file))
	//		unlink($log_file);

		// Flush log
		if(getenv('PTS_NO_FLUSH_LOGGER') == false || !file_exists($log_file))
			$fwrite = file_put_contents($log_file, null);

		if(is_writable($log_file))
			$this->log_file = $log_file;
	}
	public function default_log_file_path()
	{
		if(is_writable('/var/log') && (PTS_MODE == 'WEB_CLIENT' || defined('PHOROMATIC_SERVER') || defined('PTS_IS_DAEMONIZED_SERVER_PROCESS') || getenv('PTS_SERVER_PROCESS')))
			$log_file = '/var/log/';
		else
			$log_file = PTS_USER_PATH;

		return $log_file;
	}
	public function clear_log()
	{
		if($this->log_file == null)
			return;
		file_put_contents($this->log_file, null);
	}
	public function log($message, $date_prefix = true)
	{
		if($this->log_file == null)
			return;

		$message = pts_user_io::strip_ansi_escape_sequences($message);
		file_put_contents($this->log_file, ($date_prefix ? '[' . date('M ' . str_pad(date('j'), 2, ' ', STR_PAD_LEFT) . ' H:i:s Y') . '] ' : null) . $message . PHP_EOL, FILE_APPEND);
	}
	public function get_log_file_size()
	{
		return is_file($this->log_file) ? filesize($this->log_file) : 0;
	}
	public function get_log_file_location()
	{
		return $this->log_file;
	}
	public function get_clean_log()
	{
		$log = $this->get_log();
		$log = pts_user_io::strip_ansi_escape_sequences($log);

		return $log;
	}
	public function get_log()
	{
		return file_get_contents($this->get_log_file_location());
	}
	public static function add_to_log($message)
	{
		static $logger = null;

		if($logger == null)
			$logger = new pts_logger();

		$logger->log($message);
	}
	public function report_error($level, $message, $file, $line)
	{
		$error_string = '[' . $level . '] ';
		if(strpos($message, PHP_EOL) === false)
		{
			$error_string .= $message . ' ';
		}
		else
		{
			foreach(pts_strings::trim_explode(PHP_EOL, $message) as $line_count => $line_string)
			{
				$error_string .= $line_string . PHP_EOL . str_repeat(' ', strlen($level) + 3);
			}
		}

		if($file != null)
		{
			$error_string .= 'in ' . basename($file, '.php');
		}
		if($line != 0)
		{
			$error_string .=  ':' . $line;
		}

		$this->log($error_string);
	}
}

?>
