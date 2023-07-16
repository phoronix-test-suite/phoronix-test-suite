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

	public function __construct($log_file = null, $file_name = null, $flush_log_if_present = true, $allow_including_std_output = false)
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

		if($flush_log_if_present || !file_exists($log_file))
		{
			// Flush log
			if(getenv('PTS_NO_FLUSH_LOGGER') == false || !file_exists($log_file))
			{
				file_put_contents($log_file, '');
			}
		}

		if(is_writable($log_file))
			$this->log_file = $log_file;

		if($allow_including_std_output && pts_client::$display)
		{
			// Add to list of loggers indicating interest/relevance for possibly including std/cli output
			pts_logger_intercept_display::$loggers_interested_in_std_output[$this->log_file] = &$this;
			self::update_log_cli_output_state();
		}
	}
	public static function update_log_cli_output_state()
	{
		if(pts_env::read('LOG_CLI_OUTPUT'))
		{
			// enable
			if(pts_client::$display instanceof pts_display_mode_interface && !empty(pts_logger_intercept_display::$loggers_interested_in_std_output))
			{
				pts_client::$display = new pts_logger_intercept_display(pts_client::$display);
			}
		}
		else
		{
			// disable/restore to original
			if(pts_client::$display instanceof pts_logger_intercept_display && pts_client::$display->underlying_display instanceof pts_display_mode_interface)
			{
				pts_client::$display = pts_client::$display->underlying_display;
			}
		}
	}
	public static function default_log_file_path()
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
	public static function is_debug_mode()
	{
		return PTS_IS_DEV_BUILD || getenv('PTS_DEBUG_LOG');
	}
	public function debug_log($message, $date_prefix = true)
	{
		if(self::is_debug_mode())
		{
			$this->log($message, $date_prefix);
		}
	}
	public function log($message, $date_prefix = true)
	{
		if($this->log_file == null)
			return;

		$traces = pts_client::is_debug_mode() ? debug_backtrace() : false;
		if($traces && isset($traces[0]))
    		{
		        $caller = $traces[1]['function'];
		        $line = $traces[0]['line'];
		        $file = basename($traces[0]['file']);
		}

		$message = pts_user_io::strip_ansi_escape_sequences($message);
		file_put_contents($this->log_file, ($date_prefix ? '[' . date('Y-m-d\TH:i:sO') . '] ' : '') . ($traces ? '[' . $caller . '('. $file . ':' . $line . ')] ' : '') . $message . PHP_EOL, FILE_APPEND);
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

class pts_logger_intercept_display
{
	public $underlying_display;
	public static $loggers_interested_in_std_output = array();

	public function __construct($display)
	{
		$this->underlying_display = $display;
	}
	public function __call($method, $args)
	{
		$intercepted_text = '';
		static $line_queued = '';

		if($this->underlying_display instanceof pts_display_mode_interface)
		{
			foreach($args as &$arg)
			{
				// Workaround PHP warning for pts_display_mode_interface args that expect refs passed
				$arg = &$arg;
			}
			ob_start();
			call_user_func_array(array($this->underlying_display, $method), $args);
			$intercepted_text = ob_get_contents();
			ob_end_clean();
			echo $intercepted_text;
		}
		if(!empty($intercepted_text) && !empty(pts_logger_intercept_display::$loggers_interested_in_std_output))
		{
			$line_queued .= pts_user_io::strip_ansi_escape_sequences($intercepted_text);

			// Wait until a line is printed in full before flushing to log due to how the display mode interface can build up strings
			if(substr($line_queued, -1) == "\n")
			{
				$lq_rebuild = '';
				foreach(explode(PHP_EOL, $line_queued) as $line)
				{
					if(function_exists('preg_replace'))
					{
						$line = preg_replace('/\s+/', ' ', $line);
					}
					// Trim excess gunk not useful for log
					$line = trim(rtrim(trim($line), '.'), '=');

					if(!empty($line))
					{
						$lq_rebuild .= $line  . PHP_EOL . str_repeat(' ', 27);
					}
				}
				foreach(pts_logger_intercept_display::$loggers_interested_in_std_output as &$logger)
				{
					if($logger)
					{
						$logger->log(trim($lq_rebuild));
					}
				}
				$line_queued = '';
			}
		}
	}
}

?>
