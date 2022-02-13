<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel

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

class pts_installed_test
{
	private $footnote_override = null;
	private $install_path = false;
	private $status = null;

	private $install_date_time = null;
	private $last_run_date_time = null;
	private $installed_version = null;
	private $average_runtime = null;
	private $last_runtime = null;
	private $last_install_time = null;
	private $times_run = 0;
	private $compiler_data = null;
	private $install_footnote = null;
	private $install_checksum = null;
	private $system_hash = null;
	private $associated_test_identifier = null;
	private $per_run_times = null;
	private $install_errors = false;
	private $runtime_errors = false;

	public function __construct(&$input)
	{
		$jsonf = array();
		if($input instanceof pts_test_profile)
		{
			$this->install_path = $input->get_install_dir();
			if(is_file($this->install_path . 'pts-install.json'))
			{
				$jsonf = json_decode(file_get_contents($this->install_path . 'pts-install.json'), true);
			}
		}
		else if(is_array($input))
		{
			$jsonf = $input;
		}

		if(empty($jsonf))
		{
			return;
		}

		$this->status = isset($jsonf['test_installation']['status']) ? $jsonf['test_installation']['status'] : 'INSTALLED';
		$this->install_date_time = isset($jsonf['test_installation']['history']['install_date_time']) ? $jsonf['test_installation']['history']['install_date_time'] : null;
		$this->last_run_date_time = isset($jsonf['test_installation']['history']['last_run_date_time']) ? $jsonf['test_installation']['history']['last_run_date_time'] : null;
		$this->installed_version = isset($jsonf['test_installation']['environment']['test_version']) ? $jsonf['test_installation']['environment']['test_version'] : null;
		$this->average_runtime = isset($jsonf['test_installation']['history']['average_runtime']) ? $jsonf['test_installation']['history']['average_runtime'] : null;
		$this->last_runtime = isset($jsonf['test_installation']['history']['latest_runtime']) ? $jsonf['test_installation']['history']['latest_runtime'] : null;
		$this->last_install_time = isset($jsonf['test_installation']['history']['install_time_length']) ? $jsonf['test_installation']['history']['install_time_length'] : null;
		$this->times_run = isset($jsonf['test_installation']['history']['times_run']) ? $jsonf['test_installation']['history']['times_run'] : 0;
		$this->per_run_times = isset($jsonf['test_installation']['history']['per_run_times']) ? $jsonf['test_installation']['history']['per_run_times'] : array();
		$this->compiler_data = isset($jsonf['test_installation']['environment']['compiler_data']) ? $jsonf['test_installation']['environment']['compiler_data'] : null;
		$this->install_footnote = isset($jsonf['test_installation']['environment']['install_footnote']) ? $jsonf['test_installation']['environment']['install_footnote'] : null;
		$this->install_checksum = isset($jsonf['test_installation']['environment']['install_checksum']) ? $jsonf['test_installation']['environment']['install_checksum'] : null;
		$this->system_hash = isset($jsonf['test_installation']['environment']['system_hash']) ? $jsonf['test_installation']['environment']['system_hash'] : null;
		$this->associated_test_identifier = isset($jsonf['test_installation']['environment']['test_identifier']) ? $jsonf['test_installation']['environment']['test_identifier'] : null;
		$this->install_errors = isset($jsonf['test_installation']['errors']['install']) ? $jsonf['test_installation']['errors']['install'] : false;
		$this->runtime_errors = isset($jsonf['test_installation']['errors']['runtime']) ? $jsonf['test_installation']['errors']['runtime'] : false;
	}
	public function get_array()
	{
		// JSON output
		$to_json = array();
		$to_json['test_installation']['status'] = $this->get_install_status();
		$to_json['test_installation']['environment']['test_identifier'] = $this->get_associated_test_identifier();
		$to_json['test_installation']['environment']['test_version'] = $this->get_installed_version();
		$to_json['test_installation']['environment']['install_checksum'] = $this->get_installed_checksum();
		$to_json['test_installation']['environment']['system_hash'] = $this->get_system_hash();
		$to_json['test_installation']['environment']['compiler_data'] = $this->get_compiler_data();
		$to_json['test_installation']['environment']['install_footnote'] = $this->get_install_footnote();
		$to_json['test_installation']['history']['install_date_time'] = $this->get_install_date_time();
		$to_json['test_installation']['history']['last_run_date_time'] = $this->get_last_run_date_time();
		$to_json['test_installation']['history']['install_time_length'] = $this->get_latest_install_time();
		$to_json['test_installation']['history']['times_run'] = $this->get_run_count();
		$to_json['test_installation']['history']['average_runtime'] = $this->get_average_run_time();
		$to_json['test_installation']['history']['latest_runtime'] = $this->get_latest_run_time();
		$to_json['test_installation']['history']['per_run_times'] = $this->get_per_run_times();
		if($this->get_install_errors())
		{
			$to_json['test_installation']['errors']['install'] = $this->get_install_errors();
		}
		if($this->get_runtime_errors() && !empty($this->runtime_errors))
		{
			$to_json['test_installation']['errors']['runtime'] = $this->get_runtime_errors();
		}

		return $to_json;
	}
	public function save_test_install_metadata()
	{
		// Refresh/generate an PTS install file
		if($this->install_path)
		{
			if(!defined('JSON_PRETTY_PRINT'))
			{
				// PHP 5.3 warning fix
				define('JSON_PRETTY_PRINT', 0);
			}

			file_put_contents($this->install_path . 'pts-install.json', json_encode($this->get_array(), JSON_PRETTY_PRINT));
		}
	}
	public function is_installed()
	{
		return $this->get_install_status() == 'INSTALLED';
	}
	public function get_install_errors()
	{
		return $this->install_errors;
	}
	public function get_runtime_errors()
	{
		return $this->runtime_errors;
	}
	public function get_install_status()
	{
		return $this->status;
	}
	public function set_install_status($status)
	{
		$this->status = $status;
	}
	public function get_install_log_location()
	{
		return $this->install_path . 'install.log';
	}
	public function get_install_path()
	{
		return $this->install_path;
	}
	public function get_associated_test_identifier()
	{
		return $this->associated_test_identifier;
	}
	public function has_install_log()
	{
		return is_file($this->get_install_log_location());
	}
	public function get_install_date_time()
	{
		return $this->install_date_time;
	}
	public function get_install_date()
	{
		return substr($this->get_install_date_time(), 0, 10);
	}
	public function get_last_run_date_time()
	{
		return $this->last_run_date_time;
	}
	public function get_last_run_date()
	{
		return !empty($this->last_run_date_time) ? substr($this->last_run_date_time, 0, 10) : '';
	}
	public function get_installed_version()
	{
		return $this->installed_version;
	}
	public function get_average_run_time()
	{
		return $this->average_runtime;
	}
	public function get_latest_run_time()
	{
		return $this->last_runtime;
	}
	public function get_per_run_times()
	{
		return $this->per_run_times;
	}
	public function get_latest_install_time()
	{
		return $this->last_install_time;
	}
	public function get_run_count()
	{
		return $this->times_run;
	}
	public function get_compiler_data()
	{
		return $this->compiler_data;
	}
	public function get_install_footnote()
	{
		return !empty($this->footnote_override) ? $this->footnote_override : $this->install_footnote;
	}
	public function set_install_footnote($f = null)
	{
		return $this->footnote_override = $f;
	}
	public function get_installed_checksum()
	{
		return $this->install_checksum;
	}
	public function get_system_hash()
	{
		return $this->system_hash;
	}
	public function get_install_size()
	{
		$install_size = 0;

		if(pts_client::executable_in_path('du') && $this->install_path)
		{
			$du = trim(shell_exec('du -sk ' . $this->install_path . ' 2>&1'));
			$du = substr($du, 0, strpos($du, "\t"));
			if(is_numeric($du) && $du > 1)
			{
				$install_size = $du;
			}
		}

		return $install_size;
	}
	public function update_install_time($t)
	{
		$this->last_install_time = ceil($t);
	}
	public function test_runtime_error_handler(&$test_result_obj, &$errors)
	{
		$ch = $test_result_obj->get_comparison_hash(true, false);

		if(empty($errors))
		{
			// Clear any prior errors if set since the same test just successfully ran...
			if(isset($this->runtime_errors[$ch]))
			{
				unset($this->runtime_errors[$ch]);
			}
		}
		else
		{
			if($this->runtime_errors == false)
			{
				$this->runtime_errors = array();
			}

			// Set error in test installation metadata
			$this->runtime_errors[$ch] = array(
				'description' => $test_result_obj->get_arguments_description(),
				'date_time' => date('Y-m-d H:i:s'),
				'errors' => $errors
				);
		}
	}
	public function add_latest_run_time(&$test_result_obj, $t)
	{
		$this->last_runtime = ceil($t);
		$this->times_run++;
		$this->last_run_date_time = date('Y-m-d H:i:s');
		$individual_run_times = $test_result_obj->test_run_times;

		if(!empty($individual_run_times))
		{
			$per_run_avg = ceil(array_sum($individual_run_times) / count($individual_run_times));
			$this->add_to_run_times($this->per_run_times, 'all', $per_run_avg);
			$this->add_to_run_times($this->per_run_times, $test_result_obj->get_comparison_hash(true, false), $per_run_avg, $test_result_obj->get_arguments_description());
			$this->average_runtime = $this->get_average_time_per_run('all') * $test_result_obj->test_profile->get_default_times_to_run();
		}
	}
	public function get_average_time_per_run($index, $fallback_value = 0)
	{
		return isset($this->per_run_times[$index]['avg']) && $this->per_run_times[$index]['avg'] > 0 ? $this->per_run_times[$index]['avg'] : $fallback_value;
	}
	protected function add_to_run_times(&$run_times, $index, $value, $description = null)
	{
		if(is_array($run_times) && count($run_times) > 30)
		{
			// Only show the last 30 to avoid this file becoming too large...
			$all = $run_times['all'];
			$run_times = array_slice($run_times, -30, null, true);
			$run_times['all'] = $all;
		}

		if(!isset($run_times[$index]))
		{
			$run_times[$index] = array();
			$run_times[$index]['values'] = array();
			$run_times[$index]['total_times'] = 0;
		}

		if($description != null)
		{
			$run_times[$index]['desc'] = $description;
		}

		$run_times[$index]['total_times']++;
		array_unshift($run_times[$index]['values'], $value);

		if(isset($run_times[$index]['values'][20]))
		{
			$run_times[$index]['values'] = array_slice($run_times[$index]['values'], 0, 20);
		}
		$run_times[$index]['avg'] = ceil(array_sum($run_times[$index]['values']) / count($run_times[$index]['values']));
	}
	public function update_install_data(&$test_profile, $compiler_data, $install_footnote, $install_failed = false)
	{
		$this->install_errors = false;
		if($install_failed == false)
		{
			$this->set_install_status('INSTALLED');
		}
		else
		{
			$this->set_install_status('INSTALL_FAILED');
			if(is_array($install_failed) && !empty($install_failed))
			{
				$this->install_errors = $install_failed;
			}
		}

		$this->compiler_data = $compiler_data;
		$this->install_footnote = $install_footnote;
		$this->associated_test_identifier = $test_profile->get_identifier();
		$this->installed_version = $test_profile->get_test_profile_version();
		$this->install_checksum = $test_profile->get_installer_checksum();
		$this->system_hash = phodevi::system_id_string();
		$this->install_date_time = date('Y-m-d H:i:s');
	}
}

?>
