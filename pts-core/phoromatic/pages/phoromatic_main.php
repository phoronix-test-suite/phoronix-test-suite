<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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


class phoromatic_main implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Main';
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
		echo phoromatic_webui_header_logged_in();

		$main = '<h1>Phoromatic</h1>
				<h2>Welcome</h2>
				<p>Phoromatic is the remote management and test orchestration component to the <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>. Phoromatic allows you to exploit the Phoronix Test Suite\'s vast feature-set across multiple systems over the LAN/WAN, manage entire test farms of systems for benchmarking via a centralized interface, centrally collect test results, and carry out other enteprise-focused tasks. To get started with your new account, the basic steps to get started include:</p>
				<ol>
					<li>Connect/sync the Phoronix Test Suite client systems (the systems to be benchmarked) to this account. In the simplest form, you just need to run the following command on the test systems: <strong>phoronix-test-suite phoromatic.connect ' . phoromatic_web_socket_server_addr() . '</strong>. For more information view the instructions on the <a href="?systems">systems page</a>.</li>
					<li>Configure your <a href="?settings">account settings</a>.</li>
					<li><a href="?schedules">Create a test schedule</a>. A schedule is for running test(s) on selected system(s) on a routine, timed basis or whenever a custom trigger is passed to the Phoromatic server. A test schedule could be for running benchmarks on a daily basis, whenever a new Git commit is applied to a code-base, or other events occurred. You can also enrich the potential by adding pre/post-test hooks for ensuring the system is set to a proper state for benchmarking.</li>
					<li>View your <a href="?results">test results</a>.</li>
					<li>If you like Phoromatic and the Phoronix Test Suite for enterprise testing, please <a href="http://commercial.phoronix-test-suite.com/">contact us</a> for commercial support, our behind-the-firewall licensed versions of Phoromatic and OpenBenchmarking.org, custom engineering services, and other professional services. It\'s not without corporate support that we can continue to develop this leading Linux benchmarking software in our Phoronix mission of enriching the Linux hardware experience. If you run into any problems with our open-source software or would like to contribute patches, you can do so via our <a href="https://github.com/phoronix-test-suite/phoronix-test-suite">GitHub</a>.</li>
				</ol>


				<hr />

			<h2>Results</h2>
			<div class="pts_phoromatic_info_box_area">
				<div style="float: left; width: 100%;">
					<ul>
						<li><h1>Today\'s Test Results</h1></li>
						<a href=""><li>Title 1<br /><em>The daily mainline kernel performance tracking.</em></li></a>
						<a href=""><li>Title 2<br /><em>Monitoring the performance of various Mesa/Gallium3D drivers.</em></li></a>
					</ul>
				</div>
				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Yesterday\'s Test Results</h1></li>
						<a href=""><li>Test<br /><em>sdf sdfsdf dsfds fds fds fdsf dsf dsf dsfdsf ds</em></li></a>
						<a href=""><li>Test<br /><em>sdf dsfdsf dsfdsfds fdsfds fds fds fdsfdsfds fdsfdsfsdfdsfdsfds fsdfsdfsdfsdf.</em></li></a>
					</ul>
				</div>
				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Other Test Results This Week</h1></li>
						<a href=""><li>Core i7 4770K<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
						<a href=""><li>Radeon R9 270X<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
					</ul>
				</div>

			</div>
			<h2>Systems</h2>
			<div class="pts_phoromatic_info_box_area">

				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Recent System Activity</h1></li>
						<a href=""><li>Core i7 4770K<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
						<a href=""><li>Radeon R9 270X<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
					</ul>
				</div>
				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Recent System Warnings &amp; Errors</h1></li>
						<a href=""><li>Core i7 4770K<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
						<a href=""><li>Radeon R9 270X<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
					</ul>
				</div>
			</div>';

		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
