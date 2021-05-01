<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Phoronix Test Suite - Phoromatic - Automated Linux Benchmark Management &amp; Testing</title>
<link href="phoromatic.css?201412045370" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="Phoronix Test Suite, open-source benchmarking, Linux benchmarking, automated testing, performance tuning, Linux test orchestration, benchmark management, multi system benchmarking" />
<link rel="shortcut icon" href="favicon.ico" />
</head>
<body style="background: #FFF;">
<div id="pts_phoromatic_top_header">
	<div id="pts_phoromatic_logo"><a href="index.php"><img src="images/phoromatic_logo.png" /></a></div><ul><li>Automated Linux Benchmark Management &amp; Test Orchestration</li></ul><div style="float: right; padding: 25px 70px 0 0;"></div></div>
<div id="pts_phoromatic_main_box"><h1>Phoromatic</h1>
<p><img style="float: right;" src="images/phoromatic-graph.jpg" />


Phoromatic is the remote management system of the <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>. Phoromatic allows the automatic (hence the name <em>Phoro-matic</em>) scheduling of tests, remote installation of new tests, and the management of multiple test systems all through an intuitive, easy-to-use web interface. Tests can be scheduled to automatically run on a routine basis across multiple test systems. The test results are then available from this centralized, web-based location. Any test available via <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a> can be run via Phoromatic.</p>
<p>The latest-generation Phoromatic server and client are built into the Phoronix Test Suite 5.4 code-base and newer. Phoromatic was originally introduced with Phoronix Test Suite 2 in 2009 and has advanced a great deal, especially with the modern Phoronix Test Suite 5 code-base. Phoromatic has also matured from being a Phoromatic.com hosted instance with behind-the-firewall licensing option to having a full-featured, built-in server instance integrated as open-source software within the Phoronix Test Suite.</p>
<p>Phoromatic allows for scheduling benchmarks across systems to occur on either a timed basis (with varying day and time options), on a manual basis, or on an externally triggered basis -- allowing for unlimited possibilities such as hooking in and triggering new tests to take place whenever a new Git commit occurs or other external criteria are met. Like the Phoronix Test Suite, Phoromatic is completely extensible on the client and server ends for meeting the needs of any organization in need of Linux performance/stress management.</p>
<hr />
<h1>Phoromatic Benefits</h1>
<h3>Automated Scheduling</h3>
<p>Whether it be every evening at 6:00PM, once every Thursday at 10:00AM or somewhere in between, Phoromatic can schedule tests to be run at user-defined intervals. The testing schedules can be updated through Phoromatic web interface. After the test(s) have run, the results will be immediately uploaded to Phoromatic.</p>
<h3>Extensible</h3>
<p>Any test profile or test suite that is compliant with the Phoronix Test Suite specifications will work with Phoromatic. Phoromatic is able to leverage the hundreds of test profiles and dozens of test suites currently in the Phoronix Test Suite via <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a>, along with any custom/proprietary test profiles you or your company utilize.</p>
<h3>Remote Testing</h3>
<p>Once the test system is setup, all testing and management of that system can be done remotely. There is no need to excute Phoronix Test Suite commands locally using the GUI or command line version, but instead nearly all of the same features are accessible from the Phoromatic interface. The Phoromatic Server can also control waking systems via WoL when tests are issued, shutting down systems when idling, and other management tasks.</p>
<h3>Multi-System Support</h3>
<p>A single Phoromatic account is able to manage multiple test systems running the Phoronix Test Suite. Phoromatic supports grouping together test systems, tagging, and other features to support effectively managing many test systems. From the Phoromatic interface, installed system hardware and software from a given system can also be viewed. Systems can be spread across a private LAN or spread across several locations via the Internet.</p>
<h3>Turn-Key Deployment</h3>
<p>No additional software needs to be installed to support Phoromatic; all that's needed is Phoronix Test Suite 5.4 (Phoromatic was introduced with Phoronix Test Suite 2 but later Phoromatic updates have dropped older client compatibility) or later. New test systems can easily be synced with a given Phoromatic account by running a single command from the Phoronix Test Suite client.</p>
<p>The Phoromatic Server can be quickly and easily deployed with the only new presented dependency compared to Phoronix Test Suite clients is on PHP SQLite support. With modern versions of PHP-CLI, the Phoromatic Server is completely self-hosting for its web service.</p>
<p>Phoronix Test Suite client systems with Avahi / zero-conf networking support can automatically find Phoromatic Servers on their LAN for connecting to accounts, obtaining download caches, and utilizing other Phoromatic functionality.</p>
<h3>Result Management</h3>
<p>Test results are automatically uploaded to the Phoromatic account and remain private unless you opt to upload them to Phoronix Global. From the Phoromatic interface, results from multiple test systems can easily be compared and multiple results from the same systems can be used to track performance over time. There are also options to look at the statistical significance of the results and other features to efficienctly and effectively analyze the system's performance.</p>
<h3>Multi-User Support</h3>
<p>If deploying a Phoromatic Server within an organization, there's also support for allowing multiple user accounts to be associated with the same data and other systems. This was previously an enterprise-only feature that's now supported via the open-source code as of Phoronix Test Suite 5.4</p>
<h3>Local Caching</h3>
<p>The Phoromatic Server automatically allows for caching of Phoronix Test Suite files and OpenBenchmarking.org test profile/suite caches. This allows for the Phoronix Test Suite to be more easily deployed within organizations where the systems otherwise do not have Internet access for obtaining the necessary support files.</p>
<h3>Dashboard</h3>
<p>The Phoromatic Dashboard allows viewing the state of all connected systems in one concise view from seeing their test state to hardware/software details, estimated time to completion, and other system information.</p>
<h3>E-Mail Notifications</h3>
<p>The Phoromatic Server is able to send out notifications of new results being available, systems that appear hung, immediate alerts of system errors, and other important information so that it can be dealt with in a timely manner.</p>
<h3>Open-Source</h3>
<p>The Phoronix Test Suite is licensed under the GNU GPL. With being open-source, the client and server can be easily extended to suit your organization's needs. Phoronix Media is able to provide custom engineering and support services around the Phoronix Test Suite and Phoromatic.</p>
<h1>Getting Started</h1>
<p>With Phoronix Test Suite 5.4 or newer, getting started can be as easy as <strong>phoronix-test-suite start-phoromatic-server</strong> to deploy a Phoromatic Server with HTTP access for the UI. Setting up client systems can be as simple as <strong>phoronix-test-suite phoromatic.connect</strong> and there's Upstart/systemd files available for easy access to. More details can be found via the <a href="http://www.phoronix-test-suite.com/documentation/">Phoronix Test Suite documentation</a>.</p>
<p>The Phoronix Test Suite source-code is <a href="https://github.com/phoronix-test-suite/phoronix-test-suite/">hosted via GitHub</a>.</p>
<h1>Support &amp; Contact</h1>
<p>Commercial support, custom engineering, and other services are available via <a href="http://www.phoronix-test-suite.com/?k=contact">contacting Phoronix Media</a>. Community-based support is available via <a href="https://github.com/phoronix-test-suite">GitHub</a>.</p>
</div><div id="pts_phoromatic_bottom_footer">
<div style="float: right; padding: 2px 10px; overflow: hidden;"><a href="http://openbenchmarking.org/" style="margin-right: 20px;"><img src="images/ob-white-logo.png" /></a> <a href="http://www.phoronix-test-suite.com/"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewbox="0 0 76 41" width="76" height="41" preserveAspectRatio="xMinYMin meet">
  <path d="m74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9" stroke="#c8d905" stroke-width="4" fill="none" />
</svg></a></div>
<p style="margin: 6px 15px;">Copyright &copy; 2008 - <?php echo @date('Y'); ?> by <a href="http://www.phoronix-media.com/">Phoronix Media</a>. All rights reserved.<br />
All trademarks used are properties of their respective owners.<br />The Phoronix Test Suite, Phoromatic, and OpenBenchmarking.org are products of Phoronix Media.</p></div></body>
</html>

