<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014, Phoronix Media
	Copyright (C) 2014, Michael Larabel
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

	public function __construct($log_file = null)
	{
		if($log_file == null)
		{
			if(is_writable('/var/log') && (PTS_MODE == 'WEB_CLIENT' || defined('PHOROMATIC_SERVER') || getenv('PTS_SERVER_PROCESS')))
				$log_file = '/var/log/';
			else
				$log_file = PTS_USER_PATH;

			$log_file .= (defined('PHOROMATIC_SERVER') ? 'phoromatic' : 'phoronix-test-suite') . '.log';
		}

	//	if(file_exists($log_file))
	//		unlink($log_file);

		// Flush log
		if(getenv('PTS_NO_FLUSH_LOGGER') == false || !file_exists($log_file))
			$fwrite = file_put_contents($log_file, null);

		if(is_writable($log_file))
			$this->log_file = $log_file;
	}
	public function clear_log()
	{
		if($this->log_file == null)
			return;
		file_put_contents($this->log_file, null);
	}
	public function log($message)
	{
		if($this->log_file == null)
			return;

		file_put_contents($this->log_file, '[' . date('D M ' . str_pad(date('j'), 2, ' ', STR_PAD_LEFT) . ' H:i:s Y') . '] ' . $message . PHP_EOL, FILE_APPEND);
	}
	public function get_log_file_location()
	{
		return $this->log_file;
	}
	public static function add_to_log($message)
	{
		static $logger = null;

		if($logger == null)
			$logger = new pts_logger();

		$logger->log($message);
	}
}

?>
