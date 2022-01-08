<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class pts_phoroql
{
	public static function evaluate_search_tree($tree, $join = 'AND', $callback = '')
	{
		$matches = false;

		foreach($tree as $i => $el)
		{
			$b = false;
			if($i === 'AND' || $i === 'OR')
			{
				$b = self::evaluate_search_tree($el, $i, $callback);
			}
			else if(isset($el['query']))
			{
				$b = call_user_func($callback, $el['query']);
				if($el['not'])
				{
					$b = !$b;
				}
			}
			else if(is_array($el))
			{
				$b = self::evaluate_search_tree($el, $join, $callback);
			}

			if($join == 'AND')
			{
				if(!$b)
				{
					return false;
				}
				$matches = true;
			}
			else if($join == 'OR')
			{
				if($b)
				{
					return true;
				}

				$matches = $matches || $b;

			}

		}

		return $matches;
	}
	public static function search_query_to_tree($query)
	{
		// TODO: very basic right now, work out nested expressions, etc
		$tree = array();
		$forming = '';
		$not = false;

		$words = explode(' ', $query);
		for($i = 0, $l = count($words); $i < $l; $i++)
		{
			if(empty($words[$i]))
			{
				continue;
			}

			$is_last = ($i + 1) == $l;
			$next_word = !$is_last ? $words[($i + 1)] : false;

			switch($words[$i])
			{
				case 'AND':
				case '&&':
					self::add_expression($tree, 'AND', $forming, $not);
					break;
				case 'OR':
				case '||':
					self::add_expression($tree, 'OR', $forming, $not);
					break;
				case 'NOT':
					$not = true;
					break;
				default:
					$forming .= $words[$i] . ' ';
					if($is_last)
					{
						//$forming .= $words[$i] . ' ';
						self::add_expression($tree, 'DONE', $forming, $not);
					}
					break;
			}
		}
		return $tree;
	}
	protected static function add_expression(&$tree, $action, &$query, &$not)
	{
		if(empty($action) || empty($query))
		{
			return false;
		}

		static $last_expr;
		static $els;
		if($action != $last_expr)
		{
			if($action == 'DONE')
			{
				$els[] = array(
					'query' => trim($query),
					'not' => $not,
					);
			}
			if($last_expr != null && !empty($els))
			{
				$tree[] = array($last_expr => $els);
			}
			else if($action == 'DONE' && !empty($els))
			{
				$tree[] = $els;
			}
			$els = array();
			if($action == 'DONE')
			{
				$last_expr = null;
				return;
			}
			else
			{
				$last_expr = $action;
			}
		}

		$els[] = array(
			'query' => trim($query),
			'not' => $not,
			);
		$not = false;
		$query = null;
	}
}

?>
