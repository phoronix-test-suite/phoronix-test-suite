<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2026, Phoronix Media
	Copyright (C) 2026, Michael Larabel
	powenetics.php: Continuous per-rail power capture for the Powenetics v2 PMD

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

class powenetics extends pts_module_interface
{
	const module_name = 'Powenetics v2 Power Monitor';
	const module_version = '1.0.0';
	const module_description = 'This module provides per-rail power measurement support for the Powenetics v2 multi-channel PC power measurement device (PMD). Setting the POWENETICS environment variable to the serial device (e.g. /dev/ttyUSB0 or COM5), to "auto" for auto-discovery, or to a replay-stream file will enable continuous per-rail power capture during benchmarking with per-rail power graphs added to the result file.';
	const module_author = 'Phoronix Media';

	private static $result_identifier = null;
	private static $successful_test_run_request = null;
	private static $individual_test_run_request = null;
	private static $logger_proc = null;
	private static $logger_pipes = null;
	private static $session_dir = null;
	private static $rails_to_report = array();
	private static $interval_ms = 100;
	private static $perf_per_watt = false;

	public static $module_store_vars = array('POWENETICS', 'POWENETICS_RAILS', 'POWENETICS_PERF_PER_WATT', 'POWENETICS_INTERVAL');

	public static function module_environment_variables()
	{
		return array('POWENETICS', 'POWENETICS_RAILS', 'POWENETICS_PERF_PER_WATT', 'POWENETICS_INTERVAL');
	}
	public static function module_info()
	{
		$info = null;
		$info .= PHP_EOL . 'The Powenetics v2 module captures per-rail power measurements from the Cybenetics/Hardware Busters Powenetics v2 device and injects per-rail power graphs into the benchmark result file. To enable it during benchmarking, set the POWENETICS environment variable to the serial device path, "auto", or to a replay-stream file:'
			. PHP_EOL . PHP_EOL . '    POWENETICS=/dev/ttyUSB0 PTS_MODULES=powenetics phoronix-test-suite benchmark stream'
			. PHP_EOL . PHP_EOL . 'Environment variables:'
			. PHP_EOL . '  POWENETICS               Serial device path (e.g. /dev/ttyUSB0 or COM5), "auto", or a replay-stream file.'
			. PHP_EOL . '  POWENETICS_RAILS         Comma-separated rails/aggregates to graph (default: cpu,gpu,total; "all" for every channel).'
			. PHP_EOL . '  POWENETICS_PERF_PER_WATT When set, adds a performance-per-Watt graph using average total system power.'
			. PHP_EOL . '  POWENETICS_INTERVAL      Downsampling bucket size in milliseconds (default: 100, minimum: 10).'
			. PHP_EOL . PHP_EOL . 'User commands:'
			. PHP_EOL . '  powenetics.test                   One-shot diagnostic printing all 13 rails plus CPU/GPU/Total aggregates (hardware validation).'
			. PHP_EOL . '  powenetics.simulate <file> <secs> Generate a synthetic replay stream for testing without hardware.'
			. PHP_EOL . '  powenetics.logger <dir>           Internal capture child; invoked automatically during benchmarking.'
			. PHP_EOL;

		return $info;
	}
	public static function user_commands()
	{
		return array('logger' => 'run_logger', 'test' => 'run_test', 'simulate' => 'write_simulated_stream');
	}

	//
	// Helpers
	//

	private static function php_binary()
	{
		$php = getenv('PHP_BIN');
		if(empty($php))
		{
			// Fall back to the running interpreter so direct php invocations work anywhere
			$php = PHP_BINARY;
		}
		return $php;
	}
	private static function selected_rails()
	{
		$rails = pts_env::read('POWENETICS_RAILS');
		if(empty($rails))
		{
			return array('cpu', 'gpu', 'total');
		}
		if(strtolower($rails) == 'all')
		{
			return pts_powenetics::device_tokens();
		}

		$out = array();
		$all_tokens = pts_powenetics::device_tokens();
		foreach(pts_strings::comma_explode($rails) as $r)
		{
			$r = trim($r);
			if(in_array($r, $all_tokens))
			{
				$out[] = $r;
			}
		}
		return empty($out) ? array('cpu', 'gpu', 'total') : $out;
	}
	private static function read_interval_ms()
	{
		$interval = pts_env::read('POWENETICS_INTERVAL');
		if(is_numeric($interval) && $interval >= 10)
		{
			return intval($interval);
		}
		return 100;
	}

	//
	// User Commands
	//

	public static function run_test($args)
	{
		$source = getenv('POWENETICS');
		if(empty($source))
		{
			echo PHP_EOL . 'Set the POWENETICS environment variable to the serial device, "auto", or a replay-stream file first.' . PHP_EOL . PHP_EOL;
			return;
		}

		$device = pts_powenetics::find_device($source);
		if(empty($device))
		{
			echo PHP_EOL . 'No Powenetics v2 device could be found for: ' . $source . PHP_EOL . PHP_EOL;
			return;
		}

		$pw = new pts_powenetics($device);
		if(!$pw->open())
		{
			echo PHP_EOL . 'Could not open the Powenetics v2 device: ' . $device . PHP_EOL . PHP_EOL;
			return;
		}

		// Average a short window of frames to smooth the 1 kHz stream
		$rail_sums = array();
		$rail_table = pts_powenetics::rail_table();
		foreach($rail_table as $ch => $rail)
		{
			$rail_sums[$rail[0]] = 0;
		}
		$aggregate_sums = array();
		foreach(pts_powenetics::aggregate_table() as $token => $unused)
		{
			$aggregate_sums[$token] = 0;
		}

		$frames = 0;
		$target_frames = 200;
		for($i = 0; $i < ($target_frames * 4) && $frames < $target_frames; $i++)
		{
			$frame = $pw->read_frame();
			if($frame === false)
			{
				continue;
			}
			$frames++;
			foreach($rail_table as $ch => $rail)
			{
				$rail_sums[$rail[0]] += pts_powenetics::rail_watts($frame, $rail[0]);
			}
			foreach($aggregate_sums as $token => $unused)
			{
				$aggregate_sums[$token] += pts_powenetics::rail_watts($frame, $token);
			}
		}
		$pw->close();

		if($frames == 0)
		{
			echo PHP_EOL . 'No valid frames were read from: ' . $device . PHP_EOL . PHP_EOL;
			return;
		}

		echo PHP_EOL . 'Powenetics v2 Diagnostic (' . $device . ', averaged over ' . $frames . ' frames)' . PHP_EOL . PHP_EOL;
		$tabled = array();
		foreach($rail_table as $ch => $rail)
		{
			$tabled[] = array('  ' . $rail[1] . ': ', pts_math::set_precision($rail_sums[$rail[0]] / $frames, 2) . ' Watts');
		}
		$tabled[] = array('  ----', '');
		foreach(pts_powenetics::aggregate_table() as $token => $agg)
		{
			$tabled[] = array('  ' . $agg[0] . ': ', pts_math::set_precision($aggregate_sums[$token] / $frames, 2) . ' Watts');
		}
		echo pts_user_io::display_text_table($tabled) . PHP_EOL . PHP_EOL;
	}
	public static function run_logger($args)
	{
		// Internal child process: captures the 1 kHz stream, downsamples to interval
		// buckets, and appends 'epoch_ms,watts' rows to per-rail log files. Windows-safe:
		// uses sentinel files rather than signals for coordination.
		$session_dir = is_array($args) ? (isset($args[0]) ? $args[0] : null) : $args;
		if(empty($session_dir) || !is_dir($session_dir))
		{
			fwrite(STDERR, 'powenetics.logger requires a valid session directory argument.' . PHP_EOL);
			return;
		}
		$session_dir = rtrim($session_dir, '/\\') . '/';

		$source = getenv('POWENETICS');
		$device = pts_powenetics::find_device($source);
		if(empty($device))
		{
			fwrite(STDERR, 'powenetics.logger could not find a Powenetics v2 device for: ' . $source . PHP_EOL);
			touch($session_dir . 'logger-done');
			return;
		}

		$pw = new pts_powenetics($device);
		if(!$pw->open())
		{
			fwrite(STDERR, 'powenetics.logger could not open the Powenetics v2 device: ' . $device . PHP_EOL);
			touch($session_dir . 'logger-done');
			return;
		}

		$interval_ms = self::read_interval_ms();
		$tokens = pts_powenetics::device_tokens();
		$handles = array();
		foreach($tokens as $token)
		{
			$handles[$token] = fopen($session_dir . 'log-' . $token, 'a');
		}

		touch($session_dir . 'logger-ready');

		$bucket_start = round(microtime(true) * 1000);
		$sums = array();
		foreach($tokens as $token)
		{
			$sums[$token] = 0;
		}
		$bucket_frames = 0;
		$last_sequence = null;
		$sequence_anomalies = 0;
		$stop_file = $session_dir . 'stop';

		while(true)
		{
			$frame = $pw->read_frame();
			$now = round(microtime(true) * 1000);

			if($frame !== false)
			{
				foreach($tokens as $token)
				{
					$w = pts_powenetics::rail_watts($frame, $token);
					if($w != -1)
					{
						$sums[$token] += $w;
					}
				}
				$bucket_frames++;

				// Sequence continuity diagnostics (no checksum in protocol); real
				// firmware occasionally repeats a sequence number, so only a
				// session summary is reported rather than a line per anomaly
				if($last_sequence !== null && $frame['sequence'] != (($last_sequence + 1) & 0xFFFF))
				{
					$sequence_anomalies++;
				}
				$last_sequence = $frame['sequence'];
			}
			else
			{
				// Avoid spinning hot if the stream stalls (e.g. device unplugged)
				usleep(1000);
			}

			if(($now - $bucket_start) >= $interval_ms)
			{
				if($bucket_frames > 0)
				{
					foreach($tokens as $token)
					{
						$avg = $sums[$token] / $bucket_frames;
						fwrite($handles[$token], $now . ',' . pts_math::set_precision($avg, 2) . PHP_EOL);
						fflush($handles[$token]);
					}
				}
				$bucket_start = $now;
				$bucket_frames = 0;
				foreach($tokens as $token)
				{
					$sums[$token] = 0;
				}

				if(is_file($stop_file))
				{
					break;
				}
			}
		}

		foreach($handles as $h)
		{
			fclose($h);
		}
		$pw->close();
		if($sequence_anomalies > 0)
		{
			fwrite(STDERR, 'powenetics.logger: ' . $sequence_anomalies . ' sequence anomalies (repeated/dropped packets) across the session.' . PHP_EOL);
		}
		touch($session_dir . 'logger-done');
	}
	public static function write_simulated_stream($args)
	{
		$file = is_array($args) ? (isset($args[0]) ? $args[0] : null) : $args;
		$seconds = is_array($args) && isset($args[1]) ? intval($args[1]) : 60;
		if(empty($file))
		{
			echo PHP_EOL . 'Usage: powenetics.simulate <output-file> <seconds>' . PHP_EOL . PHP_EOL;
			return;
		}
		if($seconds < 1)
		{
			$seconds = 60;
		}

		$fp = fopen($file, 'wb');
		if($fp === false)
		{
			echo PHP_EOL . 'Could not open ' . $file . ' for writing.' . PHP_EOL . PHP_EOL;
			return;
		}

		$packets = $seconds * 1000;
		$sequence = 0;
		for($p = 0; $p < $packets; $p++)
		{
			// Deterministic waveforms so the parser/logger output can be verified.
			// Phase is derived from the packet index (1 kHz sample rate).
			$t = $p / 1000.0;
			$channels = array();
			for($ch = 0; $ch < pts_powenetics::CHANNEL_COUNT; $ch++)
			{
				$channels[$ch] = self::simulated_channel($ch, $t);
			}

			$packet = self::pack_frame($sequence, $channels);
			fwrite($fp, $packet);
			$sequence = ($sequence + 1) & 0xFFFF;

			// Inject 3 junk bytes every 5000 packets to exercise the resync logic
			if($p > 0 && ($p % 5000) == 0)
			{
				fwrite($fp, chr(0x00) . chr(0xFF) . chr(0x13));
			}
		}
		fclose($fp);

		echo PHP_EOL . 'Wrote ' . $packets . ' simulated Powenetics v2 packets (' . $seconds . 's) to ' . $file . PHP_EOL . PHP_EOL;
	}
	private static function simulated_channel($ch, $t)
	{
		// Returns array('volts' => float, 'amps' => float) with deterministic waveforms.
		// EPS/PCIe rails carry sinusoidal load; standby rails sit near zero.
		$two_pi = 2 * M_PI;
		switch($ch)
		{
			case 0: // atx-3v3
				return array('volts' => 3.3, 'amps' => 0.5 + 0.1 * sin($two_pi * 0.2 * $t));
			case 1: // atx-5v-standby
				return array('volts' => 5.0, 'amps' => 0.05);
			case 2: // atx-12v
				return array('volts' => 12.0, 'amps' => 1.0 + 0.3 * sin($two_pi * 0.15 * $t));
			case 3: // atx-5v
				return array('volts' => 5.0, 'amps' => 0.8 + 0.2 * sin($two_pi * 0.1 * $t));
			case 4: // eps-12v-1
				return array('volts' => 12.0, 'amps' => 6.0 + 2.0 * sin($two_pi * 0.5 * $t));
			case 5: // atx12vo-12v-standby
				return array('volts' => 12.0, 'amps' => 0.02);
			case 6: // eps-12v-3
				return array('volts' => 12.0, 'amps' => 0.5 + 0.2 * sin($two_pi * 0.5 * $t));
			case 7: // eps-12v-2
				return array('volts' => 12.0, 'amps' => 5.0 + 1.5 * sin($two_pi * 0.5 * $t + 1.0));
			case 8: // pcie-12v-3
				return array('volts' => 12.0, 'amps' => 4.0 + 2.0 * sin($two_pi * 0.3 * $t));
			case 9: // pcie-12v-2
				return array('volts' => 12.0, 'amps' => 4.5 + 2.0 * sin($two_pi * 0.3 * $t + 0.5));
			case 10: // pcie-slot-3v3
				return array('volts' => 3.3, 'amps' => 0.3 + 0.1 * sin($two_pi * 0.25 * $t));
			case 11: // pcie-slot-12v
				return array('volts' => 12.0, 'amps' => 2.0 + 1.0 * sin($two_pi * 0.3 * $t));
			case 12: // pcie-12v-1
				return array('volts' => 12.0, 'amps' => 5.0 + 2.5 * sin($two_pi * 0.3 * $t + 1.0));
			default:
				return array('volts' => 0.0, 'amps' => 0.0);
		}
	}
	private static function pack_frame($sequence, $channels)
	{
		// Encode a 69-byte packet: magic + u16 BE sequence + 13 x (u16 BE mV, u24 BE mA)
		$packet = chr(pts_powenetics::MAGIC_0) . chr(pts_powenetics::MAGIC_1);
		$packet .= chr(($sequence >> 8) & 0xFF) . chr($sequence & 0xFF);

		for($ch = 0; $ch < pts_powenetics::CHANNEL_COUNT; $ch++)
		{
			$mv = intval(round($channels[$ch]['volts'] * 1000));
			$ma = intval(round($channels[$ch]['amps'] * 1000));
			if($mv < 0) { $mv = 0; }
			if($ma < 0) { $ma = 0; }
			$packet .= chr(($mv >> 8) & 0xFF) . chr($mv & 0xFF);
			$packet .= chr(($ma >> 16) & 0xFF) . chr(($ma >> 8) & 0xFF) . chr($ma & 0xFF);
		}

		return $packet;
	}

	//
	// Lifecycle
	//

	public static function __run_manager_setup(&$test_run_manager)
	{
		$source = getenv('POWENETICS');
		if(empty($source))
		{
			return pts_module::MODULE_UNLOAD;
		}

		$device = pts_powenetics::find_device($source);
		if(empty($device) || !pts_powenetics::probe($device))
		{
			echo PHP_EOL . 'No usable Powenetics v2 device found for POWENETICS=' . $source . '; unloading module.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}

		self::$rails_to_report = self::selected_rails();
		self::$interval_ms = self::read_interval_ms();
		self::$perf_per_watt = pts_env::read('POWENETICS_PERF_PER_WATT') != false;

		if(pts_module_manager::is_module_attached('system_monitor') && stripos(pts_env::read('MONITOR'), 'powenetics') !== false)
		{
			echo PHP_EOL . 'WARNING: Both the powenetics module and system_monitor are targeting the Powenetics device. The serial port is exclusive-access; sensor reads may return no data while the logger holds the port.' . PHP_EOL;
		}

		echo PHP_EOL . 'Powenetics v2 Power Monitoring Enabled (' . $device . '). Rails: ' . implode(', ', self::$rails_to_report) . '.' . PHP_EOL . PHP_EOL;

		// Won't be useful if the results aren't being saved
		$test_run_manager->force_results_save();
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		self::$result_identifier = $test_run_manager->get_results_identifier();
	}
	public static function __pre_test_run($test_run_request)
	{
		self::$individual_test_run_request = clone $test_run_request;

		// Fresh per-test session directory for the logger's sentinel + log files
		self::$session_dir = pts_module::save_dir() . 'logs/' . time() . '-' . mt_rand(1000, 9999) . '/';
		pts_file_io::mkdir(pts_module::save_dir());
		pts_file_io::mkdir(pts_module::save_dir() . 'logs');
		pts_file_io::mkdir(self::$session_dir);

		$command = '"' . self::php_binary() . '" "' . PTS_PATH . 'pts-core/phoronix-test-suite.php" powenetics.logger "' . self::$session_dir . '"';
		// Child stdout/stderr go to files rather than pipes: an undrained pipe buffer
		// would eventually block the logger's diagnostic writes and stall the capture
		$descriptor_spec = array(
			0 => array('pipe', 'r'),
			1 => array('file', self::$session_dir . 'logger-output.log', 'a'),
			2 => array('file', self::$session_dir . 'logger-errors.log', 'a')
			);
		self::$logger_proc = proc_open($command, $descriptor_spec, self::$logger_pipes, null, null);

		if(!is_resource(self::$logger_proc))
		{
			echo PHP_EOL . 'Powenetics v2: failed to spawn the logger process.' . PHP_EOL;
			self::$logger_proc = null;
			return;
		}
		if(isset(self::$logger_pipes[0]) && is_resource(self::$logger_pipes[0]))
		{
			fclose(self::$logger_pipes[0]);
		}

		// Wait up to 10 seconds for the logger to signal readiness
		$waited = 0;
		while(!is_file(self::$session_dir . 'logger-ready') && $waited < 10000)
		{
			usleep(50000);
			$waited += 50;
		}
		if(!is_file(self::$session_dir . 'logger-ready'))
		{
			echo PHP_EOL . 'Powenetics v2: the logger process did not signal readiness; no power data will be captured for this test. See ' . self::$session_dir . 'logger-errors.log' . PHP_EOL;
		}
	}
	public static function __calling_test_script($test_run_request)
	{
		// Mark when the actual test execution begins; pre-marker samples are dropped later
		if(self::$session_dir != null)
		{
			file_put_contents(self::$session_dir . 'start-marker', round(microtime(true) * 1000));
		}
	}
	public static function __post_test_run_success($test_run_request)
	{
		self::$successful_test_run_request = clone $test_run_request;
	}
	public static function __post_test_run()
	{
		if(self::$logger_proc != null && self::$session_dir != null)
		{
			touch(self::$session_dir . 'stop');

			// Wait up to 10 seconds for the logger to flush and exit
			$waited = 0;
			while(!is_file(self::$session_dir . 'logger-done') && $waited < 10000)
			{
				usleep(50000);
				$waited += 50;
			}

			proc_close(self::$logger_proc);
			self::$logger_proc = null;
			self::$logger_pipes = null;
		}
	}
	public static function __post_test_run_process(&$result_file)
	{
		if(self::$successful_test_run_request && self::$session_dir != null)
		{
			$start_marker = 0;
			if(is_file(self::$session_dir . 'start-marker'))
			{
				$start_marker = intval(trim(file_get_contents(self::$session_dir . 'start-marker')));
			}

			$total_avg = self::inject_rail_results($result_file, $start_marker);

			if(self::$perf_per_watt && $total_avg > 0)
			{
				self::inject_perf_per_watt($result_file, $total_avg);
			}
		}

		self::$successful_test_run_request = null;
		self::$individual_test_run_request = null;
		self::$session_dir = null;
	}

	//
	// Result injection
	//

	private static function read_rail_log($token, $start_marker)
	{
		$file = self::$session_dir . 'log-' . $token;
		if(!is_file($file))
		{
			return array();
		}

		$values = array();
		foreach(explode(PHP_EOL, file_get_contents($file)) as $line)
		{
			$line = trim($line);
			if(empty($line) || strpos($line, ',') === false)
			{
				continue;
			}
			list($epoch_ms, $watts) = explode(',', $line, 2);
			if($start_marker > 0 && $epoch_ms < $start_marker)
			{
				// Drop rows captured before the test script actually started
				continue;
			}
			if(is_numeric($watts))
			{
				$values[] = $watts;
			}
		}
		return $values;
	}
	private static function inject_rail_results(&$result_file, $start_marker)
	{
		$total_avg = 0;

		foreach(self::$rails_to_report as $token)
		{
			$values = self::read_rail_log($token, $start_marker);

			if(count($values) <= 6)
			{
				// system_monitor guard: too few samples for a meaningful graph
				continue;
			}

			if($token == 'total')
			{
				$total_avg = pts_math::arithmetic_mean($values);
			}

			$original_parent_hash = self::$successful_test_run_request->get_comparison_hash(true, false);
			$test_result = clone self::$successful_test_run_request;
			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_display_format('LINE_GRAPH');
			$test_result->test_profile->set_result_scale('Watts');
			$test_result->test_profile->set_result_proportion('LIB');

			$label = 'Powenetics ' . pts_powenetics::readable_token_name($token) . ' Power Monitor';
			$test_result->set_used_arguments_description($label);
			$test_result->set_used_arguments('powenetics ' . $token . ' ' . $test_result->get_arguments());
			$test_result->test_result_buffer = new pts_test_result_buffer();
			$test_result->test_result_buffer->add_test_result(self::$result_identifier, implode(',', $values), implode(',', $values));
			$test_result->set_parent_hash($original_parent_hash);

			$ro = $result_file->add_result_return_object($test_result);
			if($ro)
			{
				pts_client::$display->test_run_success_inline($ro);
			}
		}

		if($total_avg == 0)
		{
			// 'total' may not be among the selected rails; compute it for perf-per-watt if needed
			$total_values = self::read_rail_log('total', $start_marker);
			if(count($total_values) > 6)
			{
				$total_avg = pts_math::arithmetic_mean($total_values);
			}
		}

		return $total_avg;
	}
	private static function inject_perf_per_watt(&$result_file, $total_avg)
	{
		// Mirrors system_monitor::process_perf_per_sensor(): only HIB BAR_GRAPH results
		// yield a meaningful performance-per-Watt graph
		if(!self::$successful_test_run_request || self::$successful_test_run_request->test_profile->get_display_format() != 'BAR_GRAPH' || self::$successful_test_run_request->test_profile->get_result_proportion() != 'HIB')
		{
			return;
		}

		$active_result = self::$successful_test_run_request->active->get_result();
		if(!is_numeric($active_result) || $active_result <= 0)
		{
			return;
		}

		$original_parent_hash = self::$successful_test_run_request->get_comparison_hash(true, false);
		$test_result = clone self::$successful_test_run_request;
		$test_result->test_profile->set_identifier(null);
		$test_result->test_profile->set_result_scale($test_result->test_profile->get_result_scale() . ' Per Watt');
		$test_result->test_result_buffer = new pts_test_result_buffer();
		$test_result->test_result_buffer->add_test_result(self::$result_identifier, round($active_result / $total_avg, 3), null, array('install-footnote' => 'Average total system power: ' . pts_math::set_precision($total_avg, 2) . ' Watts (Powenetics v2).'));
		$test_result->set_parent_hash($original_parent_hash);

		$ro = $result_file->add_result_return_object($test_result);
		if($ro)
		{
			pts_client::$display->test_run_success_inline($ro);
		}
	}
}

?>
