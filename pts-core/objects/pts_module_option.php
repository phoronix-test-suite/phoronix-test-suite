<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts_module_option.php: The object for handling persistent module options that can be controlled by the end-user

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

class pts_module_option
{
	var $option_identifier;
	var $option_question;
	var $option_supported_values;
	var $option_default_value;
	var $option_function_check;

	public function __construct($identifier, $question_string, $supported_values = null, $default_value = null, $function_to_check = null)
	{
		$this->option_identifier = $identifier;
		$this->option_question = $question_string;
		$this->option_supported_values = $supported_values;
		$this->option_default_value = $default_value;
		$this->option_function_check = $function_to_check;
	}
	public function get_identifier()
	{
		return $this->option_identifier;
	}
	public function get_question()
	{
		return $this->option_question;
	}
	public function get_default_value()
	{
		return $this->option_default_value;
	}
	public function get_formatted_question()
	{
		$question_string = $this->get_question();

		if($this->default_value != null)
		{
			$question_string .= " [" . $this->default_value . "]";
		}

		$question_string .= ": ";

		return $question_string;
	}
	public function is_supported_value($input)
	{
		$supported = false;

		if(is_array($this->option_supported_values))
		{
			if(in_array($input, $this->option_supported_values))
			{
				$supported = true;
			}
		}
		else if(empty($input) && $this->option_default_value != null)
		{
			$supported = true;
		}
		else
		{
			switch($this->option_supported_values)
			{
				case "NUMERIC":
					if(is_numeric($input))
					{
						$supported = true;
					}
					break;
				case "NUMERIC_DASH":
					if(!empty($input) && strlen(pts_remove_chars($input, true, false, false, true)) == strlen($input))
					{
						$supported = true;
					}
					break;
				case "ALPHA_NUMERIC":
					if(!empty($input) && strlen(pts_remove_chars($input, true, false, true)) == strlen($input))
					{
						$supported = true;
					}
					break;
				case "HTTP_URL":
					if(substr($input, 0, 7) == "http://")
					{
						$supported = true;
					}
					break;
				case "":
					$supported = true;
					break;
			}
		}

		if($supported && !empty($this->option_function_check))
		{
			$supported = (call_user_func($this->option_function_check, $input) == true);
		}

		return $supported;
	}
}

?>
