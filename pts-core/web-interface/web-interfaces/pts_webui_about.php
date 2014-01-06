<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2014, Michael Larabel

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


class pts_webui_about implements pts_webui_interface
{
	public static function page_title()
	{
		return 'About';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		echo '<div style="text-align: center; margin: 20px;">';
		echo '<script type="text/javascript"> pts_logo(); </script>';
		echo '<h1 style="color: #000;">' . pts_title(true) . '</h1>';
		echo '<h3 style="color: #000;">Copyright &#xA9; 2008 - ' . date('Y') . ' by Phoronix Media.<br />
			All trademarks used are properties of their respective owners. All rights reserved.</h3>';
		echo '<p style="text-align: justify; margin: 30px 40px 0;">The Phoronix Test Suite is a cross-platform, open-source automated benchmarking and testing software platform. The Phoronix Test Suite provides all the necessary components for carrying out tests in a fully-automanted manner from test downloading and installation to execution and results analysis. The Phoronix Test Suite also supports many extras like simultaneous system monitoring support, enterprise features like multi-system test management, regression testing and tracking, and many other features. New tests can be easily added to the Phoronix Test Suite through its unique, XML and shell script based architecture.</p>';
		echo '<p style="text-align: justify; margin: 20px 40px 10px;">Phoronix Test Suite 1.0 was publicly released in 2008 after being in development for several years as an internal tool for use at <a href="http://www.phoronix.com/">Phoronix.com</a> and was developed in conjunction with leading IHVs, ISVs, and other organizations. The lead developer of the Phoronix Test Suite is <a href="http://www.michaellarabel.com/">Michael Larabel</a>. Developed in conjunction with the Phoronix Test Suite are the enterprise-focused <a href="http://www.phoromatic.com/">Phoromatic</a> and <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a> components.</p>';
		echo '</div>';
	}
}

?>
