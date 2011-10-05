<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel

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

class bilde_renderer_web
{
	public static function browser_compatibility_check($user_agent)
	{
		if(isset($_REQUEST['force_format']))
		{
			return $_REQUEST['force_format'];
		}

		$user_agent .= ' ';
		$selected_renderer = 'SVG';

		// Yahoo Slurp, msnbot, and googlebot should always be served SVG so no problems there

		if(($p = strpos($user_agent, 'Gecko/')) !== false)
		{
			// Mozilla Gecko-based browser (Firefox, etc)
			$gecko_date = substr($user_agent, ($p + 6));
			$gecko_date = substr($gecko_date, 0, 6);

			// Around Firefox 3.0 era is best
			// Firefox 2.0 mostly works except text might not show...
			if($gecko_date < 200702)
			{
				$selected_renderer = 'PNG';
			}
		}
		else if(($p = strpos($user_agent, 'AppleWebKit/')) !== false)
		{
			// Safari, Google Chrome, Google Chromium, etc
			$webkit_ver = substr($user_agent, ($p + 12));
			$webkit_ver = substr($webkit_ver, 0, strpos($webkit_ver, ' '));

			// Webkit 532.2 534.6 (WebOS 3.0.2) on WebOS is buggy for SVG
			// iPhone OS is using 533 right now
			if($webkit_ver < 533 || strpos($user_agent, 'hpwOS') !== false)
			{
				$selected_renderer = 'PNG';
			}

			if(($p = strpos($user_agent, 'Android ')) !== false)
			{
				$android_ver = substr($user_agent, ($p + 8), 3);

				// Android browser doesn't support SVG.
				// Google bug report 1376 for Android - http://code.google.com/p/android/issues/detail?id=1376
				// Looks like it might work though in 3.0 Honeycomb
				if($android_ver < 3.0)
				{
					$selected_renderer = 'PNG';
				}
			}
		}
		else if(($p = strpos($user_agent, 'Opera/')) !== false)
		{
			// Opera
			$ver = substr($user_agent, ($p + 6));
			$ver = substr($ver, 0, strpos($ver, ' '));

			// 9.27, 9.64 displays most everything okay
			if($ver < 9.27)
			{
				$selected_renderer = 'PNG';
			}

			// text-alignment is still fucked as of 11.50/12.0
			$selected_renderer = 'PNG';
		}
		else if(($p = strpos($user_agent, 'Epiphany/')) !== false)
		{
			// Older versions of Epiphany. Newer versions should report their Gecko or WebKit appropriately
			$ver = substr($user_agent, ($p + 9));
			$ver = substr($ver, 0, 4);

			if($ver < 2.22)
			{
				$selected_renderer = 'PNG';
			}
		}
		else if(($p = strpos($user_agent, 'KHTML/')) !== false)
		{
			// KDE Konqueror as of 4.7 is still broken for SVG
			$selected_renderer = 'PNG';
		}
		else if(($p = strpos($user_agent, 'MSIE ')) !== false)
		{
			$ver = substr($user_agent, ($p + 5), 1);

			// Microsoft Internet Explorer 9.0 finally seems to do SVG right
			if($ver < 10 && $ver != 1)
			{
				$selected_renderer = 'PNG';
			}
		}
		else if(strpos($user_agent, 'facebook') !== false)
		{
			// Facebook uses this string for its Like/Share crawler, so serve it a PNG so it can use it as an image
			$selected_renderer = 'PNG';
		}

		return $selected_renderer;
	}
}

?>
