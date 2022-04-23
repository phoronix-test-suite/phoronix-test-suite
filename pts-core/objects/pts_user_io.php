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

class pts_user_io
{
	public static $readline_completion_possibilities = null;

	public static function read_user_input($prompt = null)
	{
		echo $prompt;
		return trim(fgets(STDIN));
	}
	public static function strip_ansi_escape_sequences($output)
	{
		if(function_exists('preg_replace'))
		{
			$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', '', $output);
			$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', '', $output);
			$output = preg_replace('/[\x03|\x1a]/', '', $output);
		}

		return $output;
	}
	public static function prompt_user_input($question, $allow_null = false, $password = false, $line_start = null)
	{
		do
		{
			echo PHP_EOL . $line_start . pts_client::cli_just_bold($question . ': ');
			if($password && pts_client::executable_in_path('stty') && !phodevi::is_windows())
			{
				system('stty -echo');
			}
			$answer = pts_user_io::read_user_input();
			if($password && pts_client::executable_in_path('stty') && !phodevi::is_windows())
			{
				system('stty echo');
			}
		}
		while(!$allow_null && empty($answer));

		return $answer;
	}
	public static function prompt_numeric_input($question, $allow_null = false)
	{
		do
		{
			echo PHP_EOL . pts_client::cli_just_bold($question . ': ');
			$answer = pts_user_io::read_user_input();
		}
		while((!$allow_null && empty($answer)) || !is_numeric($answer));

		return $answer;
	}
	public static function display_text_list($list_items, $line_start = '- ')
	{
		$list = null;

		foreach($list_items as &$item)
		{
			$list .= $line_start . $item . PHP_EOL;
		}

		return $list;
	}
	public static function display_packed_list(&$list)
	{
		$terminal_width = pts_client::terminal_width();
		$longest_item = 0;
		foreach($list as &$item)
		{
			if(isset($item[$longest_item + 1]))
			{
				$longest_item = strlen($item);
			}
		}

		$items_per_line = floor($terminal_width / ($longest_item + 1));
		$i = 0;
		foreach($list as &$item)
		{
			echo $item . str_repeat(' ', $longest_item - strlen($item) + 1);
			$i++;
			if($i % $items_per_line == 0)
			{
				echo PHP_EOL;
			}
		}
	}
	public static function display_text_table(&$table, $prepend_to_lines = null, $extra_width_to_column = 0, $min_width = 0, $border = false, $bold_row = -1, $color_rows = false)
	{
		$column_widths = array();
		$formatted_table = null;
		$longest_line = 0;

		for($r = 0; $r < count($table); $r++)
		{
			for($c = 0; is_array($table[$r]) && $c < count($table[$r]); $c++)
			{
				if(!isset($column_widths[$c]) || isset($table[$r][$c][$column_widths[$c]]))
				{
					$column_widths[$c] = $table[$r][$c] == null ? 0 : strlen($table[$r][$c]);
				}
			}
		}

		for($r = 0; $r < count($table); $r++)
		{
			$line = null;
			for($c = 0; is_array($table[$r]) && $c < count($table[$r]); $c++)
			{
				if($border)
				{
					$line .= '| ';
				}

				$line .= $table[$r][$c];

				$m = (max($min_width, 1 + $extra_width_to_column + $column_widths[$c]) - ($table[$r][$c] != null ? strlen($table[$r][$c]) : 0));
				if($m > 0)
				{
					$line .= str_repeat(' ', $m);
				}
			}
			$line = $prepend_to_lines . $line;
			if($border)
			{
				$line = $line . '|';
			}
			$longest_line = max($longest_line, strlen($line));
			if($color_rows && isset($color_rows[$r]))
			{
				$line = pts_client::cli_colored_text($line, $color_rows[$r], ($r == $bold_row));
			}
			else if($r == $bold_row)
			{
				$line = pts_client::cli_just_bold($line);
			}
			$formatted_table .= ($r == 0 ? '' : PHP_EOL) . $line;
			if($r == 0 && $border)
			{
				$line = null;
				for($c = 0; $c < count($table[$r]); $c++)
				{
					if($border)
					{
						$line .= '| ';
					}

					//$line .= $table[$r][$c];

					$m = (max($min_width, 1 + $extra_width_to_column + $column_widths[$c]));
					$line .= str_repeat('-', $m - 1) . ' ';
				}
				$formatted_table .= PHP_EOL . $line . '|';
			}
		}

		if($border)
		{
			$formatted_table = PHP_EOL. PHP_EOL . $formatted_table . PHP_EOL;
			//$formatted_table = str_repeat('-', $longest_line) . PHP_EOL . $formatted_table . PHP_EOL . str_repeat('-', $longest_line) . PHP_EOL;
		}

		return $formatted_table;
	}
	public static function prompt_bool_input($question, $default = true, $question_id = 'UNKNOWN')
	{
		// Prompt user for yes/no question
		/*if BATCH MODE
		{
			switch($question_id)
			{
				default:
					$auto_answer = 'true';
					break;
			}

			$answer = pts_strings::string_bool($auto_answer);
		}*/
		if($default === true)
		{
			$def = 'Y/n';
		}
		else if($default === false)
		{
			$def = 'y/N';
		}
		else
		{
			$def = 'y/n';
		}

		$question .= ' (' . $def . '): ';

		$answer = -1;
		do
		{
			pts_client::$display->generic_prompt(pts_client::cli_just_bold($question));
			$input = strtolower(pts_user_io::read_user_input());
			switch($input)
			{
				case 'y':
					$answer = true;
					break;
				case 'n':
					$answer = false;
					break;
				case '':
					$answer = $default;
					break;
			}
		}
		while($answer === -1);

		return $answer;
	}
	public static function readline_completion_handler($input)
	{
		$possibilities = array();
		$readline_info = readline_info();
		if(isset($readline_info['end']))
		{
			$input = substr($readline_info['line_buffer'], 0, $readline_info['end']);
		}
		$input_length = strlen($input);

		if(is_array(self::$readline_completion_possibilities))
		{
			foreach(self::$readline_completion_possibilities as $possibility)
			{
				if(substr($possibility, 0, $input_length) == $input)
				{
					$possibilities[] = $possibility;
				}
			}
		}

		//$possibilities[] = '';
		sort($possibilities);
		return $possibilities;
	}
	public static function prompt_text_menu($user_string, $options_r, $allow_multi_select = false, $return_index = false, $line_prefix = null)
	{
		$option_count = count($options_r);

		if($option_count == 1)
		{
			if($allow_multi_select)
			{
				return $return_index ? array_keys($options_r) : $options_r;
			}
			else
			{
				return $return_index ? pts_arrays::last_element(array_keys($options_r)) : array_pop($options_r);
			}
		}

		$select = array();

		do
		{
			echo PHP_EOL;
			$key_index = array();
			foreach(array_keys($options_r) as $i => $key)
			{
				$key_index[($i + 1)] = $key;
				$line_offset = strlen($line_prefix . ($i + 1) . ': ' . str_repeat(' ', strlen($option_count) - strlen(($i + 1))));
				echo $line_prefix . pts_client::cli_just_bold(($i + 1) . ': ') . str_repeat(' ', strlen($option_count) - strlen(($i + 1))) . str_replace(PHP_EOL, PHP_EOL . str_repeat(' ', $line_offset), $options_r[$key]) . PHP_EOL;
			}
			if($allow_multi_select)
			{
				echo $line_prefix . pts_client::cli_colored_text('** Multiple items can be selected, delimit by a comma. **', 'gray') . PHP_EOL;
			}

			if(function_exists('readline') && function_exists('readline_completion_function'))
			{
				pts_user_io::$readline_completion_possibilities = array_merge($options_r, array_keys($key_index));
				readline_completion_function(array('pts_user_io', 'readline_completion_handler'));
				$select_choice = readline($line_prefix . $user_string . ': ');
			}
			else
			{
				echo $line_prefix . pts_client::cli_just_bold($user_string . ': ');
				$select_choice = pts_user_io::read_user_input();
			}

			foreach(($allow_multi_select ? pts_strings::comma_explode($select_choice) : array($select_choice)) as $choice)
			{
				$choice_trimmed = pts_strings::trim_spaces($choice);
				if(isset($key_index[($c = $choice)]) || isset($key_index[($c = $choice_trimmed)]))
				{
					$select[] = $key_index[$c];
				}
				else if(in_array(($c = $choice), $options_r) || in_array(($c = $choice_trimmed), $options_r))
				{
					$select[] = array_search($c, $options_r);
				}
				else if($allow_multi_select && strpos($choice, '-') !== false)
				{
					$choice_range = pts_strings::trim_explode('-', $choice);

					if(count($choice_range) == 2 && is_numeric($choice_range[0]) && is_numeric($choice_range[1]) && isset($key_index[$choice_range[0]]) && isset($key_index[$choice_range[1]]))
					{
						for($i = min($choice_range); $i <= max($choice_range); $i++)
						{
							$select[] = $key_index[$i];
						}
					}
				}
			}
		}
		while(!isset($select[0]));

		if($return_index == false)
		{
			foreach($select as &$index)
			{
				$index = $options_r[$index];
			}
		}

		// Technically implode shouldn't be needed as should just be $select[0] to return
		return $allow_multi_select ? $select : implode(',', $select);
	}
}

?>
