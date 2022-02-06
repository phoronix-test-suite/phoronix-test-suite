<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2022, Phoronix Media
	Copyright (C) 2010 - 2022, Michael Larabel

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


class pts_ae_data
{
	private $db;
	private $ae_dir;
	private $cpu_index;

	public function __construct($output_dir)
	{
		if(!is_dir($output_dir))
		{
			echo 'valid directory needed!';
			return false;
		}
		if(!extension_loaded('sqlite3'))
		{
			echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('PHP SQLite3') . ' support must first be enabled.' . PHP_EOL . PHP_EOL;
			return false;
		}

		$this->ae_dir = $output_dir . '/';
		$db_flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
		$this->db = new SQLite3($this->ae_dir . 'temp.db', $db_flags);
		$this->db->busyTimeout(10000);
		pts_file_io::mkdir($this->ae_dir . 'comparison-hashes/');
		pts_file_io::mkdir($this->ae_dir . 'component-data/');
		pts_file_io::mkdir($this->ae_dir . 'component-heavy/');

		$result = $this->db->query('PRAGMA user_version;');
		$result = $result->fetchArray();
		$version = isset($result['user_version']) && is_numeric($result['user_version']) ? $result['user_version'] : 0;
		switch($version)
		{
			case 0:
				// Create
				$this->db->exec('CREATE TABLE `analytics_results` (	`ID`	INTEGER PRIMARY KEY AUTOINCREMENT, `ResultReference` TEXT,  `ComparisonHash`	TEXT,	`Component`	INTEGER, `RelatedComponent`	INTEGER, `DateTime`	INTEGER, `SystemType`	TEXT, `SystemLayer`	TEXT, `Result`	REAL NOT NULL);');
				$this->db->exec('CREATE INDEX `comp_hashes` ON `analytics_results` (`ComparisonHash`,`Result`);');
				$this->db->exec('CREATE INDEX `result_and_component_search` ON `analytics_results` (`ComparisonHash`,`Component`,`Result`);');
				$this->db->exec('CREATE TABLE `components` (`ComponentID`	INTEGER PRIMARY KEY AUTOINCREMENT,`Component`	TEXT UNIQUE,`Category`	INTEGER,`TimesAppeared`	INTEGER);');
				$this->db->exec('CREATE INDEX `quick` ON `components` (	`ComponentID`,	`Component`);');
				$this->db->exec('CREATE INDEX `by_cat` ON `components` (`Component`,`Category`,`TimesAppeared`);');
				$this->db->exec('CREATE TABLE `component_categories` (`CategoryID`	INTEGER PRIMARY KEY AUTOINCREMENT,`Category`	TEXT UNIQUE);');
				$this->db->exec('CREATE INDEX `quick_cat` ON `component_categories` (	`CategoryID`,	`Category`);');
				$this->db->exec('CREATE TABLE `composite` (`ComparisonHash`	TEXT UNIQUE,`TestProfile`	TEXT,`Title`	TEXT,`ArgumentsDescription`	TEXT,`HigherIsBetter`	INTEGER, TestVersion TEXT, AppVersion TEXT, ResultUnit TEXT,`SampleSize`	INTEGER, Percentiles TEXT, FirstAppeared INTEGER, LastAppeared INTEGER, PRIMARY KEY(`ComparisonHash`));');
				$this->db->exec('CREATE INDEX `tp` ON `composite` (`TestProfile`);');
				$this->db->exec('CREATE UNIQUE INDEX `unq` ON `analytics_results` (`DateTime`,`Result`,`Component`,`RelatedComponent`,`ComparisonHash`);');
				//$this->db->exec('');
				//$this->db->exec('');
				//$this->db->exec('');
				$this->db->exec('PRAGMA user_version = 1');
			case 1:
				$this->db->exec('ALTER TABLE analytics_results ADD COLUMN TimeConsumed INTEGER');
				$this->db->exec('PRAGMA user_version = 2');
			case 2:
				$this->db->exec('ALTER TABLE analytics_results ADD COLUMN StdDev REAL');
				$this->db->exec('PRAGMA user_version = 3');
			case 3:
				$this->db->exec('PRAGMA journal_mode = WAL');
				$this->db->exec('PRAGMA synchronous = OFF');
				$this->db->exec('PRAGMA user_version = 4');
			case 4:
				$this->db->exec('ALTER TABLE analytics_results ADD COLUMN Arch TEXT');
				$this->db->exec('PRAGMA user_version = 5');
		}

		$this->cpu_index = json_decode(file_get_contents('http://openbenchmarking.org/extern/cpu-trans-index.php'), true);
		return true;
	}
	public function insert_composite_hash_entry_by_result_object($comparison_hash, &$result_object)
	{
		$stmt = $this->db->prepare('INSERT OR IGNORE INTO composite (ComparisonHash, TestProfile, Title, ArgumentsDescription, HigherIsBetter, TestVersion, AppVersion, ResultUnit) VALUES (:ch, :tp, :t, :ad, :hib, :tv, :av, :ru)');
		$stmt->bindValue(':ch', $comparison_hash);
		$stmt->bindValue(':tp', $result_object->test_profile->get_identifier(false));
		$stmt->bindValue(':t', $result_object->test_profile->get_title());
		$stmt->bindValue(':ad', $result_object->get_arguments_description());
		$stmt->bindValue(':hib', ($result_object->test_profile->get_result_proportion() == 'HIB' ? 1 : 0));
		$stmt->bindValue(':tv', $result_object->test_profile->get_test_profile_version());
		$stmt->bindValue(':av', $result_object->test_profile->get_app_version());
		$stmt->bindValue(':ru', $result_object->test_profile->get_result_scale());
		$result = $stmt->execute();
	}
	public function insert_result_into_analytic_results($comparison_hash, $result_reference, $component, $category, $related_component, $related_category, $result, $datetime, $system_type, $system_layer, $time_consumed, $std_dev, $arch)
	{
		$stmt = $this->db->prepare('INSERT OR IGNORE INTO analytics_results (ComparisonHash, ResultReference, Component, RelatedComponent, Result, DateTime, SystemType, SystemLayer, TimeConsumed, StdDev, Arch) VALUES (:ch, :rr, :c, :rc, :r, :dt, :st, :sl, :tc, :std, :arch)');
		$stmt->bindValue(':ch', $comparison_hash);
		$stmt->bindValue(':rr', $result_reference);
		$stmt->bindValue(':c', $this->component_to_component_id($component, $category));
		$stmt->bindValue(':rc', $this->component_to_component_id($related_component, $related_category));
		$stmt->bindValue(':r', $result);
		$stmt->bindValue(':dt', $datetime);
		$stmt->bindValue(':st', $system_type);
		$stmt->bindValue(':sl', $system_layer);
		$stmt->bindValue(':tc', $time_consumed);
		$stmt->bindValue(':std', $std_dev);
		$stmt->bindValue(':arch', $arch);
		$result = $stmt->execute();
	}
	public function component_to_component_id($component, $category)
	{
		static $cache;
		if(isset($cache[$component][$category]))
		{
			return $cache[$component][$category];
		}
		$stmt = $this->db->prepare('SELECT ComponentID FROM components WHERE Component = :c LIMIT 1');
		$stmt->bindValue(':c', $component);
		$result = $stmt ? $stmt->execute() : false;

		if($result && ($row = $result->fetchArray()))
		{
			$cache[$component][$category] = $row['ComponentID'];
			return $row['ComponentID'];
		}

		$stmt = $this->db->prepare('INSERT OR IGNORE INTO components (Component, Category) VALUES (:component, :category)');
		$stmt->bindValue(':component', $component);
		$stmt->bindValue(':category', $this->category_to_category_id($category));
		$result = $stmt->execute();
		$cache[$component][$category] = $this->db->lastInsertRowid();
		return $cache[$component][$category];
	}
	public function component_to_category($component)
	{
		static $cache;
		if(isset($cache[$component]))
		{
			return $cache[$component];
		}
		$stmt = $this->db->prepare('SELECT Category FROM components WHERE Component = :c LIMIT 1');
		$stmt->bindValue(':c', $component);
		$result = $stmt ? $stmt->execute() : false;

		if($result && ($row = $result->fetchArray()))
		{
			$cache[$component] = $this->category_id_to_category($row['Category']);
			return $row['Category'];
		}
	}
	public function component_id_to_component($component_id)
	{
		static $cache;
		if(isset($cache[$component_id]))
		{
			return $cache[$component_id];
		}
		$stmt = $this->db->prepare('SELECT Component FROM components WHERE ComponentID = :c LIMIT 1');
		$stmt->bindValue(':c', $component_id);
		$result = $stmt ? $stmt->execute() : false;

		if($result && ($row = $result->fetchArray()))
		{
			$cache[$component_id] = $row['Component'];
			return $cache[$component_id];
		}
	}
	public function category_to_category_id($category)
	{
		static $cache;
		if(isset($cache[$category]))
		{
			return $cache[$category];
		}
		$stmt = $this->db->prepare('SELECT CategoryID FROM component_categories WHERE Category = :c LIMIT 1');
		$stmt->bindValue(':c', $category);
		$result = $stmt ? $stmt->execute() : false;

		if($result && ($row = $result->fetchArray()))
		{
			$cache[$category] = $row['CategoryID'];
			return $row['CategoryID'];
		}

		$stmt = $this->db->prepare('INSERT OR IGNORE INTO component_categories (Category) VALUES (:category)');
		$stmt->bindValue(':category', $category);
		$result = $stmt->execute();
		$cache[$category] = $this->db->lastInsertRowid();
		return $cache[$category];
	}
	public function category_id_to_category($category_id)
	{
		static $cache;
		if(isset($cache[$category_id]))
		{
			return $cache[$category_id];
		}
		$stmt = $this->db->prepare('SELECT Category FROM component_categories WHERE CategoryID = :c LIMIT 1');
		$stmt->bindValue(':c', $category_id);
		$result = $stmt ? $stmt->execute() : false;

		if($result && ($row = $result->fetchArray()))
		{
			$cache[$category_id] = $row['Category'];
			return $row['Category'];
		}
	}
	public function rebuild_composite_listing()
	{
		$stmt = $this->db->prepare('SELECT * FROM composite');
		$result = $stmt ? $stmt->execute() : false;
		$json_index_master = array();
		$json_per_version_summary = array();
		$hardware_data['Processor'] = array();
		$hardware_heavy = array();

		while($result && ($row = $result->fetchArray()))
		{
			$comparison_hash = $row['ComparisonHash'];
			$first_appeared = 0;
			$last_appeared = 0;
			$component_results = array();
			$component_dates = array();
			$system_types = array();
			$timing_data = array();
			$stddev_data = array();
			$family_perf = array();
			$tested_archs = array();
			$results = $this->get_results_array_by_comparison_hash($comparison_hash, $first_appeared, $last_appeared, $component_results, $component_dates, $system_types, $timing_data, $stddev_data, $tested_archs);

			if(count($results) < 10)
			{
				continue;
			}

			$percentiles = array();
			for($i = 0; $i < 100; $i++)
			{
				$percentiles[$i] = pts_math::find_percentile($results, ($i * 0.01));

				if($percentiles[$i] > 10)
				{
					$percentiles[$i] = round($percentiles[$i], 5);
				}
			}

			$peak = max($results);

			$component_data = array();
			$comparison_components = array();
			$comparison_components_raw = array();
			foreach($component_results as $component => $d)
			{
				if(stripos($component . ' ', 'device ') !== false || stripos($component, 'unknown') !== false  || stripos($component, 'common ') !== false || is_numeric($component))
				{
					continue;
				}

				foreach($d as $related_component => $data)
				{
					if(!isset($comparison_components[$component]))
					{
						$comparison_components[$component] = array();
					}
					$comparison_components[$component] = array_merge($comparison_components[$component], $data);

					if(stripos($related_component . ' ', 'device ') !== false || stripos($related_component, 'unknown') !== false)
					{
						continue;
					}
					if($component_dates[$component][$related_component]['last_appeared'] < (time() - (31536000 * 3)))
					{
						// if no new results in 3 years, likely outdated...
						continue;
					}

					if(count($data) < 3)
					{
						continue;
					}
					$data = pts_math::remove_outliers($data, 3);
					if(count($data) < 3)
					{
						continue;
					}
					$component_data[$component][$related_component]['avg'] = round(pts_math::arithmetic_mean($data), ($peak > 60 ? 0 : 2));
					$component_data[$component][$related_component]['samples'] = count($data);
					$component_data[$component][$related_component]['first_appeared'] = $component_dates[$component][$related_component]['first_appeared'];
					$component_data[$component][$related_component]['last_appeared'] = $component_dates[$component][$related_component]['last_appeared'];
					$component_data[$component][$related_component]['system_type'] = $system_types[$component][$related_component];
				}
			}
			$component_sample_counts = array();
			foreach($comparison_components as $component => &$values)
			{
				$component_sample_counts[$component] = count($values);
				$values = pts_math::remove_outliers($values, 3);

				// FAMILY DATA
				if(isset($this->cpu_index[$component]) && !phodevi::is_fake_device($component))
				{
					// Per Core Calculations
					$brand = false;
					if(stripos($component, 'Intel') !== false)
					{
						$brand = 'Intel';
					}
					else if(stripos($component, 'AMD') !== false)
					{
						$brand = 'AMD';
					}

					if($brand)
					{
						$core_family = $this->cpu_index[$component]['CoreFamily'];
						$series = str_ireplace(array('AMD ', 'Intel ', ), '', $component);
						if(($x = stripos($series, '-Core')) !== false)
						{
							$series = substr($series, 0, $x);
							$series = substr($series, 0, strrpos($series, ' '));
						}
						if(($x = stripos($series, ' x ')) !== false)
						{
							$series = substr($series, ($x + 3));
						}
						$series = str_replace('-', ' ', $series);
						$series = substr($series, 0, strrpos($series, ' '));
						$series = str_replace(array('Dual '), '', $series);
						if(!empty($series))
						{
							$brand_comp_type = phodevi_base::system_type_to_string(phodevi_base::determine_system_type($component));
							$core_family_compare = $brand . ' ' . $core_family . ' ' . $brand_comp_type;
							if(!isset($family_perf[$core_family_compare][$series]))
							{
								$family_perf[$core_family_compare][$series] = array();
							}
							$series_compare = $brand . ' ' . $series . ' ' . $this->cpu_index[$component]['CoreCount'] . '-Core ' . $brand_comp_type;
							if(!isset($family_perf[$series_compare][$core_family]))
							{
								$family_perf[$series_compare][$core_family] = array();
							}

							$value = pts_math::arithmetic_mean($values);
							$always_hib_value = ($row['HigherIsBetter'] == 0 ? (1 / $value) * 1000 : $value) / $this->cpu_index[$component]['CPUClock'];
							$family_perf[$core_family_compare][$series][] = $always_hib_value;
							$family_perf[$series_compare][$core_family][] = $always_hib_value;
						}
					}
				}
				// END OF FAMILY DATA

				if(phodevi::is_fake_device($component) || count($values) < 3 || pts_math::percent_standard_deviation($values) > 15)
				{
					unset($component_sample_counts[$component]);
					unset($comparison_components[$component]);
					continue;
				}
			}
			uasort($comparison_components, array('pts_ae_data', 'sort_array_by_size_of_array_in_value'));
			$comparison_components = array_slice($comparison_components, 0, 300);
			$csc = array();
			$csstd = array();
			$precision = 2;
			foreach($comparison_components as $component => &$values)
			{
				$comparison_components_raw[$component] = $values;
				$csstd[$component] = pts_math::standard_deviation($values);
				$values = pts_math::arithmetic_mean($values);

				if($values < 5)
				{
					$precision = 5;
				}
				else if($values < 20)
				{
					$precision = 3;
				}
				else if($values < 60)
				{
					$precision = 2;
				}
				else if($peak > 100)
				{
					$precision = 0;
				}
				$precision = $precision > 0 ? min($precision, pts_math::get_precision($comparison_components_raw[$component])) : 0;

				$csstd[$component] = round($csstd[$component], $precision);
				$values = round($values, $precision);
				$csc[$component] = $component_sample_counts[$component];
			}

			if($row['HigherIsBetter'] == '1')
			{
				arsort($comparison_components);
			}
			else
			{
				asort($comparison_components);
			}

			// TIMING DATA Assembly
			$td = array();
			$timing_data = pts_math::remove_outliers($timing_data, 3);
			$average_time = round(array_sum($timing_data) / count($timing_data));

			$timing_percentiles = array();
			for($i = 0; $i < 100; $i++)
			{
				$timing_percentiles[$i] = pts_math::find_percentile($timing_data, ($i * 0.01));

				if($timing_percentiles[$i] > 10)
				{
					$timing_percentiles[$i] = round($timing_percentiles[$i]);
				}
			}

			if($average_time > 600)
			{
				$round_to_nearest = 60;
			}
			else if($average_time > 300)
			{
				$round_to_nearest = 30;
			}
			else
			{
				$round_to_nearest = 10;
			}
			foreach($timing_data as $time_consumed)
			{
				$time_consumed = max(1, round($time_consumed / $round_to_nearest)) * $round_to_nearest;
				if($time_consumed > 0)
				{
					if(!isset($td[$time_consumed]))
					{
						$td[$time_consumed] = 0;
					}

					$td[$time_consumed]++;
				}
			}

			// Standard Deviation Percent Data Assembly
			$stddev_data = pts_math::remove_outliers($stddev_data);
			$average_stddev = round(array_sum($stddev_data) / count($stddev_data), 2);
			$std = array();
			foreach($stddev_data as $pdev)
			{
				$pdev = round($pdev, 1);
				if(!isset($std[$pdev]))
				{
					$std[$pdev] = 0;
				}
				$std[$pdev]++;
			}

			// Tested on CPU archs
			$tested_on_archs = array();
			foreach(array_unique($tested_archs) as $tarch)
			{
				$targ = '';
				if(strpos($tarch, '=') !== false)
				{
					list($tarch, $targ) = explode('=', $tarch);
				}
				if(!isset($tested_on_archs[$tarch]))
				{
					$tested_on_archs[$tarch] = array();
				}
				if(!empty($targ) && !in_array($targ, $tested_on_archs[$tarch]))
				{
					$tested_on_archs[$tarch][] = $targ;
				}
			}

			// JSON FILE
			$json = array();
			$json['comparison_hash'] = $comparison_hash;
			$json['test_profile'] = $row['TestProfile'];
			$json['title'] = $row['Title'];
			$json['description'] = $row['ArgumentsDescription'];
			$json['test_version'] = substr($row['TestVersion'], 0, strrpos($row['TestVersion'], '.')) . '.x';
			$json['app_version'] = $row['AppVersion'];
			$json['hib'] = $row['HigherIsBetter'];
			$json['unit'] = $row['ResultUnit'];
			$json['samples'] = count($results);
			$json['sample_data'] = implode(',', $results);
			$json['run_time_avg'] = $average_time;
			$json['run_time_data'] = $td;
			$json['stddev_avg'] = $average_stddev;
			$json['stddev_data'] = $std;
			$json['first_appeared'] = $first_appeared;
			$json['last_appeared'] = $last_appeared;
			$json['tested_archs'] = $tested_on_archs;
			$json['percentiles'] = $percentiles;
			$json['components'] = $component_data;
			$json['reference_results'] = $comparison_components;
			$json['reference_results_counts'] = $csc;
			$json['reference_results_std_dev'] = $csstd;

			if($json['first_appeared'] < 1298678400)
			{
				// OpenBenchmarking launch date so anything below that would be incorrect timing
				$json['first_appeared'] = strtotime('2011-02-26');
			}

			// FAMILY PERFORMANCE
			foreach($family_perf as $brand => &$data)
			{
				foreach($data as $family => &$sp)
				{
					if(count($sp) < 4)
					{
						// Not enough data to potentially too off...
						unset($family_perf[$brand][$family]);
						continue;
					}
					$sp = array_sum($sp) / count($sp);
				}
				if(count($family_perf[$brand]) < 3)
				{
					unset($family_perf[$brand]);
				}
			}
			foreach($family_perf as $brand => &$data)
			{
				$min_family_perf = min($family_perf[$brand]);
				foreach($data as $family => &$sp)
				{
					$sp = round($sp / $min_family_perf, 2);
				}
				arsort($family_perf[$brand]);
			}
			ksort($family_perf);
			$json['family_perf'] = $family_perf;
			// EO Family Perf

			$json_encoded = json_encode($json);
			if(!empty($json_encoded))
			{
				$test_dir = base64_encode($row['TestProfile']);
				pts_file_io::mkdir($this->ae_dir . 'comparison-hashes/' . $test_dir . '/');
				file_put_contents($this->ae_dir . 'comparison-hashes/' . $test_dir . '/' . $comparison_hash . '.json', $json_encoded);

				if(!isset($json_index_master[$test_dir]))
				{
					$json_index_master[$test_dir] = array();
				}
				$json_index_master[$test_dir][str_replace('.', '', $json['test_version']) . '-' . $json['comparison_hash']] = array(
					'comparison_hash' => $json['comparison_hash'],
					'description' => $json['description'],
					'test_version' => $json['test_version'],
					'app_version' => $json['app_version'],
					'unit' => $json['unit'],
					'samples' => $json['samples'],
					'product_samples' => count($comparison_components),
					);
				if(!isset($json_per_version_summary[$test_dir][$json['test_version']] ))
				{
					$json_per_version_summary[$test_dir][$json['test_version']] = array();
				}
				$json_per_version_summary[$test_dir][$json['test_version']][$json['comparison_hash']] = array(
					//'comparison_hash' => $json['comparison_hash'],
					'description' => $json['description'],
					'test_version' => $json['test_version'],
					'app_version' => $json['app_version'],
					'unit' => $json['unit'],
					'samples' => $json['samples'],
					'timing_samples' => count($timing_data),
					'hib' => $json['hib'],
					'first_appeared' => $json['first_appeared'],
					'last_appeared' => $json['last_appeared'],
					'run_time_avg' => $json['run_time_avg'],
					'stddev_avg' => $json['stddev_avg'],
					'percentiles' => $json['percentiles'],
					'run_time_percentiles' => $timing_percentiles,
					'tested_archs' => array_keys($tested_on_archs),
					);
			}
			// EO JSON

			$stmt = $this->db->prepare('UPDATE composite SET SampleSize = :ss, Percentiles = :p, FirstAppeared = :fa, LastAppeared = :la WHERE ComparisonHash = :ch');
			$stmt->bindValue(':ss', count($results));
			$stmt->bindValue(':ch', $comparison_hash);
			$stmt->bindValue(':p', implode(',', $percentiles));
			$stmt->bindValue(':fa', $first_appeared);
			$stmt->bindValue(':la', $last_appeared);
			$stmt->execute();
			
			//
			// Update/Create Component JSON
			//
			
			if(count($comparison_components) >= 20 && $json['stddev_avg'] < 3 && $json['run_time_avg'] > 45 && $last_appeared > strtotime('-5 months'))
			{
				foreach($comparison_components as $component => $value)
				{
					$component_category = $this->component_to_category($component);
					$this_percentile = $this->result_to_percentile($value, $percentiles, $json['hib']);
					
					if(!is_numeric($this_percentile) || $this_percentile == 0)
					{
						continue;
					}

					switch($component_category)
					{
						case 'Graphics':
						case 'Disk':
							if(strpos($component, ' x ') !== false)
							{
								// Don't want multi-disk reporting as who knows the RAID setup or if JBOD mixed in, etc
								break;
							}
						case 'Processor':
						case 'Memory':
							if(!isset($hardware_data[$component_category][$component]))
							{
								$hardware_data[$component_category][$component] = array(
									'percentiles' => array(),
									);
							}
							if(!isset($hardware_data[$component_category][$component]['percentiles'][$json['test_profile']][$json['test_version']]))
							{
								$hardware_data[$component_category][$component]['percentiles'][$json['test_profile']][$json['test_version']] = array();
							}
							$hardware_data[$component_category][$component]['percentiles'][$json['test_profile']][$json['test_version']] = $this_percentile;

							// heavy
							if(!isset($hardware_heavy[$component_category][$component]))
							{
								$hardware_heavy[$component_category][$component] = array();
							}
							$hardware_heavy[$component_category][$component][$json['comparison_hash']] = array(
								'test_profile' => $row['TestProfile'] . '-' . $row['TestVersion'],
								'title' => $row['Title'],
								'app_version' => $row['AppVersion'],
								'description' => $row['ArgumentsDescription'],
								'hib' => $row['HigherIsBetter'] ? 'HIB': 'LIB',
								'unit' => $row['ResultUnit'],
								'value' => $value,
								'raw_values' => implode(':', $comparison_components_raw[$component]),
								);
							break;
					}
				}
			}
		}
		if(!empty($json_index_master))
		{
			foreach($json_index_master as $test_profile_dir => $test_index)
			{
				pts_arrays::natural_krsort($test_index);
				$test_index = json_encode($test_index);
				file_put_contents($this->ae_dir . 'comparison-hashes/' . $test_profile_dir . '/index.json', $test_index);
			}
		}
		if(!empty($json_per_version_summary))
		{
			foreach($json_per_version_summary as $test_profile_dir => $vd)
			{
				foreach($vd as $test_version => $test_index)
				{
					uasort($test_index, array('pts_ae_data', 'sort_by_sample_size'));
					if(count($test_index) > 24)
					{
						$test_index = array_slice($test_index, 0, 24);
					}
					$test_index = json_encode($test_index);
					file_put_contents($this->ae_dir . 'comparison-hashes/' . $test_profile_dir . '/overview-' . $test_version . '.json', $test_index);
				}
			}
		}
		foreach($hardware_data as $hw_category => $category_data)
		{
			pts_file_io::mkdir($this->ae_dir . 'component-data/' . $hw_category);
			foreach($category_data as $c => $data)
			{
				if(!isset($data['percentiles']) || count($data['percentiles']) < 4)
				{
					continue;
				}

				$de = json_encode($data);
				file_put_contents($this->ae_dir . 'component-data/' . $hw_category . '/' . $c . '.json', $de);
			}
		}
		foreach($hardware_heavy as $hw_category => $category_data)
		{
			pts_file_io::mkdir($this->ae_dir . 'component-heavy/' . $hw_category);
			foreach($category_data as $c => $data)
			{
				if(count($data) < 15)
				{
					continue;
				}

				$de = json_encode($data);
				file_put_contents($this->ae_dir . 'component-heavy/' . $hw_category . '/' . $c . '.json', $de);
			}
		}
	}
	public static function sort_by_sample_size($a, $b)
	{
		if($a['samples'] == $b['samples'])
		{
			return 0;
		}

		return $a['samples'] > $b['samples'] ? -1 : 1;
	}
	public function append_to_component_data(&$system_logs)
	{
		foreach($system_logs as $hw_category => $category_data)
		{
			foreach($category_data as $c => $data)
			{
				if($data['occurences'] < 4)
				{
					continue;
				}
				pts_file_io::mkdir($this->ae_dir . 'component-data/' . $hw_category . '/');
				$json_file = $this->ae_dir . 'component-data/' . $hw_category . '/' . $c . '.json';
				$jsond = is_file($json_file) ? json_decode(file_get_contents($json_file), true) : array();
				$jsond = is_array($jsond) && !empty($jsond) ? array_merge($jsond, $data) : $data;
				file_put_contents($this->ae_dir . 'component-data/' . $hw_category . '/' . $c . '.json', json_encode($jsond));
			}
		}
	}
	public function result_to_percentile($value, $percentiles, $hib)
	{
		$this_percentile = 0;
		if($hib == 0)
		{
			$percentiles = array_reverse($percentiles);
		}
		
		foreach($percentiles as $i => $percentile)
		{
			if($hib && $value < $percentile)
			{
				$this_percentile = ($i + 1);
				break;
			}
			else if($hib == 0 && $value > $percentile)
			{
				$this_percentile = ($i + 1);
				break;
			}
		}
		if($this_percentile == 0 && $hib)
		{
			$this_percentile = 100;
		}
		
		return $this_percentile > 0 && $this_percentile <= 100 ? $this_percentile : false;
	}
	public function sort_array_by_size_of_array_in_value($a, $b)
	{
		return count($b) - count($a);
	}
	public function get_results_array_by_comparison_hash($ch, &$first_appeared, &$last_appeared, &$component_results, &$component_dates, &$system_types, &$timing_data, &$stddev_data, &$arch)
	{
		$stmt = $this->db->prepare('SELECT Result, DateTime, Component, RelatedComponent, SystemType, SystemLayer, TimeConsumed, StdDev, Arch FROM analytics_results WHERE ComparisonHash = :ch');
		$stmt->bindValue(':ch', $ch);
		$result = $stmt ? $stmt->execute() : false;
		$results = array();
		$first_appeared = time();
		$last_appeared = 0;
		while($result && ($row = $result->fetchArray()))
		{
			if(!is_numeric($row['Result']))
			{
				continue;
			}
			$dt = $row['DateTime'];
			if($dt < $first_appeared)
			{
				$first_appeared = $dt;
			}
			else if($dt > $last_appeared || $last_appeared == 0)
			{
				$last_appeared = $dt;
			}
			$results[] = $row['Result'];
			if($row['TimeConsumed'] > 0)
			{
				$timing_data[] = $row['TimeConsumed'];
			}
			$stddev_data[] = $row['StdDev'];
			if(!empty($row['Arch']))
			{
				$arch[] = $row['Arch'];
			}
			if(!empty($row['SystemLayer']) || strlen($row['Component']) < 3)
			{
				continue;
			}

			$c = $this->component_id_to_component($row['Component']);
			$rc = $this->component_id_to_component($row['RelatedComponent']);
			if(!isset($component_results[$c][$rc]))
			{
				$component_results[$c][$rc] = array();
			}
			$component_results[$c][$rc][] = $row['Result'];
			if(!isset($component_dates[$c][$rc]))
			{
				$component_dates[$c][$rc] = array('first_appeared' => $dt, 'last_appeared' => $dt);
			}
			else
			{
				$component_dates[$c][$rc]['first_appeared'] = min($component_dates[$c][$rc]['first_appeared'], $dt);
				$component_dates[$c][$rc]['last_appeared'] = max($component_dates[$c][$rc]['last_appeared'], $dt);
			}
			$system_types[$c][$rc] = $row['SystemType'];
		}
		return $results;
	}
}
?>
