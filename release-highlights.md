# Phoronix Test Suite Release History

Phoronix Test Suite 10.8
======
#### Release Date: 25 December 2021
* Consolidated and unified handling of environment variables, exposing as well environment variables to Phoromatic
* Improved test installation and test run-time error detection and reporting
* Support improvements for macOS 12
* Support improvements for PHP 8.1
* Improved JSON result output generation
* Phoromatic now uses the unified/shared result viewer code for an improved experience, along with other code sharing improvements
* Phoromatic client support for connecting to HTTPS-only Phoromatic servers
* Phoromatic now allows optionally uploading test run-time and installation logs
* Phoromatic systems pages can now display test profile installation status overview
* Phoromatic now supports streaming/incremental result uploads for viewing tentative results as benchmarks are finished
* New sub-commands: remove-incomplete-results-from-result-file, result-file-to-html, list-failed-installs, variables, list-test-errors
* New modules: cleanup

Phoronix Test Suite 10.6
======
#### Release Date: 10 October 2021 | Codename: Tjeldsund
* Improvements to the modern result viewer, removal of the deprecated legacy result viewer
* Support for expressing test run/install errors inline as part of the result file
* Initial compatibility with PHP 8.1
* Many Phoromatic fixes & enhancements

Phoronix Test Suite 10.4
======
#### Release Date: 16 May 2021 | Codename: Ibestad
* Various optimizations and enhancements

Phoronix Test Suite 10.2
======
#### Release Date: 5 January 2021 | Codename: Harstad
* Significantly faster Phoromatic Web UI performance
* Improved support for macOS 11 Big Sur, Apple Silicon (Apple M1)
* Improved tracking of per-test/configuration run-time time requirements, improved test run-time estimation
* Support for reporting broken download mirrors to OpenBenchmarking.org
* BSD support updates (FreeBSD, DragonFlyBSD, OpenBSD, NetBSD)
* Full PHP 8.0 compatibility
* Various Phodevi software/hardware detection reporting improvements
* New modules: turbostat

Phoronix Test Suite 10.0
======
#### Release Date: 13 October 2020 | Codename: Finnsnes
* New version/overhaul of OpenBenchmarking.org
* Various hardware/software detection improvements with Phodevi
* New modules: flush_caches, test_timeout

Phoronix Test Suite 9.8
======
#### Release Date: 9 July 2020 | Codename: Nesodden
* Improved handling of test install failure when a new minor test profile update is available tu automatically try
* Improved detection of OpenCL and NVIDIA CUDA presence for avoiding some test option prompts when not supported
* Estimated test install time reporting
* Rewritten virtual test suite implementation
* Early PHP 8.0 support
* Linux AMD Energy driver support in Phodevi, NVIDIA GPU detection in WSL2, other cases

Phoronix Test Suite 9.6
======
#### Release Date: 21 April 2020 | Codename: Nittedal
* Continued improvements to the result viewer with many features added
* New sub-commands: workload-topology, analyze-run-times

Phoronix Test Suite 9.4
======
#### Release Date: 24 February 2020 | Codename: Vestby
* Numerous result viewer improvements (showing logs within viewer, UI/UX improvements, new options, deleting results)
* Support for annotating results that are then displayed below individual benchmark results
* The modern result viewer now works on Microsoft Windows platforms
* Support for showing performance-per-suite metrics
* Numerous PDF result generation enhancements
* Save test logs and save installation logs is now enabled by default
* Improve reporting on test installation failures
* New sub-commands: remove-result-from-result-file, intersect

Phoronix Test Suite 9.2
======
#### Release Date: 3 December 2019 | Codename: Hurdal
* Updated result viewer for Phoromatic Server
* macOS support updates
* Recording of CPU microcode revisions within the result file
* Various graph handling improvements
* Other fixes

Phoronix Test Suite 9.0
======
#### Release Date: 17 September 2019 | Codename: Asker
* New result viewer by default
* Confidential test/result handling improvements and ability to permanently disable result uploading support
* Offline handling improvements with now shipping a static cache of all tests/suites as of release time
* PDF report generation improvements
* New pie-chart graphing feature of wins/losses for a result file
* New sub-commands: stress-batch-run, compare-results-two-way, result-file-confidence

Phoronix Test Suite 8.8
======
#### Release Date: 13 May 2019 | Codename: Hvaler
* Initial work on new result viewer (to be completed next cycle)
* Phodevi improvements: AVX-512 VNNI detection, Intel Node Manager power reporting, OpenJDK/Java version detection improvements, RAM temperature reporting for some ARM SBCs, AMDGPU mem_busy_poercent handling, other hardware/software detection improvements
* New sub-commands: remove-run-from-result-file, remove-results-from-result-file, strict-run/strict-benchmark, result-file-raw-to-csv
* Continued Microsoft Windows support improvements

Phoronix Test Suite 8.6
======
#### Release Date: 5 February 2019 | Codename: Spydeberg
* CLI/terminal inline box plot graphing support
* New OpenBenchmarking.org auto-compare view based upon result percentiles from all public data
* New `phoronix-test-suite result-file-stats` sub-command
* New post-run statistics displayed upon test completion
* Various Windows and BSD hardware/support updates, among other Phodevi additions

Phoronix Test Suite 8.4
======
#### Release Date: 26 November 2018 | Codename: Skiptvet
* Improvements to the text-based/CLI graphs
* Improvements to CSV frame-time parsing
* Various Phodevi hardware/software detection refinements, including better IBM POWER9 detection
* Various fixes and other minor refinements, external dependency updates

Phoronix Test Suite 8.2
======
#### Release Date: 11 September 2018 | Codename: Rakkestad
* Official Docker benchmarking image of the Phoronix Test Suite for reference benchmarking based on Clear Linux and available as "phoronix/pts" on Docker
* Various ARM hardware detection improvements
* CPU power reporting support using Intel RAPL
* New sub-commands `phoronix-test-suite list-cached-tests` and `phoronix-test-suite list-all-tests`
* New DropNoisyResults user configuration option if not wanting to save "noisy" results (high variance)
* L1TF / Foreshadow mitigation reporting on Linux systems
* Initial Readline-based tab-based text completion support for different TUI fields
* New "pgo" module for easily carrying out benchmarks to analyze Profile-Guided Optimizations (PGO) compiler performance
* Various usability enhancements

Phoronix Test Suite 8.0
======
#### Release Date: 5 June 2018 | Codename: Aremark
* Rewritten and overhauled Windows 10 / Windows Server 2016 support, now considered officially supported
* Much improved BSD operating system support
* Minor macOS support improvements, including optional support for the Brew package manager
* Initial support for Termux for possible Android support in the future
* New sub-commands `phoronix-test-suite create-test-profile` and `phoronix-test-suite inspect-test-profile` and `phoronix-test-suite openbenchmarking-uploads` and `phoronix-test-suite shell`
* Much improved result search functionality from the Phoromatic Server
* The ability to create new test profiles from the Phoromatic Server web interface
* Better handling when Internet connection support is absent
* SiFive RISC-V CPU detection, Cavium ThunderX, Spectre V4 reporting and other hardware/software reporting improvements in Phodevi
* An integrated backup module for easily backing up and restoring of all PTS/Phoromatic data on a system

Phoronix Test Suite 7.8
======
#### Release Date: 14 February 2018 | Codename: Folldal
* Allow tests not part of OpenBenchmarking.org to be automatically cloned from a connected Phoromatic Server when needed
* Improved reporting around deprecated/experimental/broken test profiles
* New sub-commands `phoronix-test-suite search` and `phoronix-test-suite dump-phodevi-properties`
* Reworked Phodevi property handler and other Phodevi improvements
* Restored support for vertical bar graphs in pts_Graph
* Spectre and Meltdown CPU vulnerability reporting

Phoronix Test Suite 7.6
======
#### Release Date: 7 December 2017 | Codename: Alvdal
* Significant BSD operating system support improvements
* Official macOS High Sierra support
* Various portability updates
* External dependency updates, initial support for LEDE and MidnightBSD
* Phodevi improvements: gpu.memory-usage and memory.temp sensors, various hardware/software detection improvements

Phoronix Test Suite 7.4
======
#### Release Date: 19 September 2017 | Codename: Tynset
* `phoronix-test-suite unload-module` and `phoronix-test-suite auto-load-module` and `phoronix-test-suite network-info` sub-commands
* Inline showing of test results when benchmarking against an existing result file from the CLI
* New `perf_tips` module for reporting various performance tips to users
* New `ob_auto_compare` module to provide inline seamless OpenBenchmarking.org result references to tests currently running from CLI
* Dropped `graphics_event_checker`
* Improved screensaver handling
* Various Phodevi improvements

Phoronix Test Suite 7.2
======
#### Release Date: 8 June 2017 | Codename: Trysil
* Result parser improvements
* `phoronix-test-suite dump-file-info`, `phoronix-test-suite dump-tests-to-git`, `phoronix-test-suite dump-suites-to-git` sub-commands
* Phoromatic support for setting run priorities on test schedules

Phoronix Test Suite 7.0
======
#### Release Date: 6 March 2017 | Codename: Ringsaker
* New `phoronix-test-suite estimate-run-time` and `phoronix-test-suite winners-and-losers` sub-commands
* Phoromatic database improvements
* New system software/hardware display formatting
* Support for having one test run generate multiple test result outputs
* `phoronix-test-suite stress-run` improvements

Phoronix Test Suite 6.8
======
#### Release Date: 28 November 2016 | Codename: Tana
* BSD support improvements.
* New `phoronix-test-suite list-not-installed-tests` and `phoronix-test-suite php-conf` sub-commands
* New `flamegrapher` module
* New `results_custom_export` module
* Phodevi hardware/software detection improvements

Phoronix Test Suite 6.6
======
#### Release Date: 6 September 2016 | Codename: Loppa
* Graphing improvements
* Phoromatic web UI tweaks
* Improved disk detail reporting

Phoronix Test Suite 6.4
======
#### Release Date: 2 June 2016 | Codename: Hasvik
* `phoronix-test-suite stress-run` improvements
- Phoromatic support for stress testing
- Phoromatic support for email notifications and other features
- Watchdog module for suspending/stopping temperatures if sensor thresholds reached

Phoronix Test Suite 6.2
======
#### Release Date: 16 February 2016 | Codename: Gamvik
* Dynamic dependency handler infrastructure
* Windows support improvements
* LimitNetworkCommunication option
* Reworked generation of PDF test results
* Continued Phoromatic plumbing improvements
* Initial Vulkan detection/support

Phoronix Test Suite 6.0
======
#### Release Date: 16 November 2015 | Codename: Hammerfest
* Rework of the Phoromatic web interface
* New local results viewer using HTML+JS
* Result parsing improvements
* Rework of low-level infrastructure / underlying improvements / faster merging
* Improved SVG graph generation
* New graph rendering interface for pts_Graph

Phoronix Test Suite 5.8
======
#### Release Date: 5 June 2015 | Codename: Belev
* MIPS support improvements
* Faster rendering of result files and other data processing improvements
* Stress-run improvements
* System sensor monitoring via the Phoromatic UI
* Addition of the Phoromatic Results Export Viewer
* Various Linux hardware & software detection improvements
* Allow Phoronix Test Suite clients to be self-updated via update script passed from the Phoromatic Server
* Mongoose web server support for the Phoromatic Server's HTTP instance
* Support viewing system client logs via the Phoromatic Server UI
* Numerous other improvements to Phoromatic

Phoronix Test Suite 5.6
======
#### Release Date: 24 March 2015 | Codename: Dedilovo
* Many Phoromatic Improvements
* Phoromatic Server Search Support
* Phoromatic Server Stress-Run Controls
* Support For Commenting/Annotating Result Files
* Support For Custom System Variables To Be Used By Result File Strings
* Rootadmin additions & Controls
* Support For Results Via RSS
* Allow One-Time Benchmark Runs & Issuing Of Benchmark Tickets
* Allow Uploading Of Results To OpenBenchmarking.org Via Viewer Page
* Allow Forming Of Custom Test Suites Via Build Suite Page
* Add stress-run Sub Command To Phoronix Test Suite Client
* OS X Support Improvements

Phoronix Test Suite 5.4
======
#### Release Date: 9 December 2014 | Codename: Lipki
* Major overhaul to the built-in Phoromatic Server
* Avahi zero-conf networking support
* Improved download cache handling
* IBM POWER8 hardware detection improvements
* Various code refactoring & other improvements

Phoronix Test Suite 5.2
======
#### Release Date: 5 June 2014 | Codename: Khanino
* Tech Preview / Experimental Built-In Phoromatic Server
* Result graphing improvements, including new box plot graphs
* Phodevi software & hardware detection improvements
* Phodevi Radeon GPU usage reporting via RadeonTOP
* New Phoronix Test Suite sub-command options
* Bug-fixes and other minor enhancements

Phoronix Test Suite 5.0
======
#### Release Date: 12 March 2014 | Codename: Plavsk
* Tech Preview / Experimental HTML5 GUI
* Run-random-tests command
* Phodevi Hardware/Software Detection Improvements
* Start-up Speed Enhancements
* Numerous bug-fixes
* Assorted minor improvements

Phoronix Test Suite 4.8
======
#### Release Date: 13 August 2013 | Codename: Sokndal
* Minimum / maximum result reporting
* Frame latency / jitter testing support
* Improved hardware/software detection support
* NVIDIA/AMD AIB GPU board detection support
* Facebook HHVM (HipHop Virtual Machine) 2.1 support
* Graph coloring improvements
* System detail reporting improvements
* Phodevi hardware sensor improvements
* Phoromatic.com support improvements

Phoronix Test Suite 4.6
======
#### Release Date: 21 May 2013 | Codename: Utsira
* Compiler masking/flag improvements
* Phodevi enhancements
* DragonFlyBSD support improvements
* Support for running under Facebook HHVM HipHop Virtual Machine
* New internal-run sub-command
* Phodevi hardware/software improvements

Phoronix Test Suite 4.4
======
#### Release Date: 20 February 2013 | Codename: Forsand
* Phodevi Hardware/Software Detection Improvements
* OpenBenchmarking.org Integration Enhancements
* Improved Reporting Of Test Installation Errors
* Improved Reporting Of Test Run-Time Errors
* Improved BSD Operating System Support
* Rewritten PTS External Dependencies Handling
* Improved Compiler/User Flag Reporting On Test Results

Phoronix Test Suite 4.2
======
#### Release Date: 20 December 2012 | Codename: Randaberg
* Desktop Support Improvements
* Phodevi Support For IMPI Detection
* New auto-compare Option For Facilitating Fully Automated Comparisons
* Add list-recommended-tests Option
* Various Minor Enhancements

Phoronix Test Suite 4.0
======
#### Release Date: 23 July 2012 | Codename: Suldal
* New Result Viewer Interface
* Performance-per-Watt / Energy Monitoring Improvements
* Hardware/Software Detection Improvements
* Greater Documentation
* New Result Analytical Features

Phoronix Test Suite 3.8
======
#### Release Date: 19 March 2012 | Codename: Bygland
* Improved Disk Reporting
* Improved Compiler Option/Configuration Reporting
* New Graph Renderer
* Improved ARM / Mobile Device Support
* Download Caching Enhancements
* Re-written Graphics Event Checker
* Support For Apple Mac OS X 10.8

Phoronix Test Suite 3.6
======
#### Release Date: 13 December 2011 | Codename: Arendal
* Enhanced Support For BSD, Solaris Operating Systems
* Various Graphing Improvements
* Expanded Phodevi Library Coverage
* Greater OpenBenchmarking.org Integration
* Various Bug Fixes

Phoronix Test Suite 3.4
======
#### Release Date: 8 September 2011 | Codename: Lillesand
* MATISK Benchmarking Module
* Improved Phodevi Device Recognition
* Graphing Improvements
* Third-Party Test/Suite Uploading From The Phoronix Test Suite Client
* Continued OpenBenchmarking.org Integration Enhancements
* GNU Hurd Operating System Support

Phoronix Test Suite 3.2
======
#### Release Date: 15 June 2011 | Codename: Grimstad
* Facebook HipHop Compiler Support
* Improved Software Detection
* Improved Hardware Detection
* Support For New System Sensors
* Improved Wine Compatibility
* Interactive Text Mode Support

Phoronix Test Suite 3.0
======
#### Release Date: 26 February 2011 | Codename: Iveland
* OpenBenchmarking.org Integration
* Enhanced Multi-OS, Multi-Architecture Capabilities
* Internal Architectural Enhancements
* Improved Graph Rendering

Phoronix Test Suite 2.8
======
#### Release Date: 31 August 2010 | Codename: Torsken
* 134 Test Profiles
* 56 Test Suites + PCQS
* New Analytics Capabilities
* New Installation & External Dependency Management Support
* Improved Windows 7 x64 Support

Phoronix Test Suite 2.6
======
#### Release Date: 24 May 2010 | Codename: Lyngen
* 134 Test Profiles
* 56 Test Suites + PCQS
* New Test Results Parsing Mechanism
* New PTS Results Viewer Interface
* Phoromatic / Phoromatic Tracker Improvements
* Functional Windows 7 x64 Support

Phoronix Test Suite 2.4
======
#### Release Date: 24 February 2010 | Codename: Lenvik
* 131 Test Profiles
* 54 Test Suites + PCQS
* Palm webOS / Optware Support
* Improved *BSD OS Support
* Image Quality Comparison Support
* Improved Sensor Monitoring
* GTK2 User Interface Enhancements
* New Network Engine
* Initial Phodevi / pts-core Support On Windows

Phoronix Test Suite 2.2
======
#### Release Date: 16 November 2009 | Codename: Bardu
* 120 Test Profiles
* 50 Test Suites + PCQS
* Automated Regression Tracking Module (Autonomous Git Bisecting)
* Test Recovery Support
* Statistical Significance Support
* Anonymous Usage Reporting
* Display Mode Support
* Network Proxy Support
* Overhauled GTK2 GUI

Phoronix Test Suite 2.0
======
#### Release Date: 4 August 2009 | Codename: Sandtorg
* 109 Test Profiles
* 47 Test Suites + PCQS
* Expanded Reference System Comparisons
* Many New Test Options
* Introduction Of Phodevi Library
* Overhaul To GTK2 User Interface
* New Test Profile Options
* Initial Release Of PTS Desktop Live

Phoronix Test Suite 1.8
======
#### Release Date: 6 April 2009 | Codename: Selbu
* 90 Test Profiles
* 39 Test Suites + PCQS
* GTK2 Graphical User Interface
* Enhanced *BSD Support
* Support For Reference System Comparisons
* Image Renderer Optimizations
* Updated Test Options
<a href="?k=changes_18">Complete Change-Log</a>

Phoronix Test Suite 1.6
======
#### Release Date: 20 January 2009 | Codename: Tydal
* 89 Test Profiles
* 36 Test Suites + PCQS
* Options To Build Your Own Suite
* An Adobe PDF Generator For Test Results
* Support Multiple Arguments When Installing/Running Tests
* Introduce bilde_renderer, Add Support For Rendering Adobe Flash / SWF Graphs
* Support For Virtual Suites
* New Features In pts-core
* Numerous New Options

Phoronix Test Suite 1.4
======
#### Release Date: 3 November 2008 | Codename: Orkdal
* 84 Test Profiles
* 34 Test Suites + PCQS
* Mac OS X Support
* Cascading Test Profiles
* Self-Contained Test Profiles
* More Modules
* WINE-based Tests
* OpenSolaris 2008.11 Support
* SVG Graph Rendering Option
<a href="?k=changes_14">Complete Change-Log</a>

Phoronix Test Suite 1.2
======
#### Release Date: 3 September 2008 | Codename: Malvik
* 76 Test Profiles
* 38 Test Suites
* Improved Hardware Detection
- Multi-Monitor Support
- Multi-GPU Support
* Modular Plug-in Framework
- System Monitoring Module
- E-Mail Results Module
- Graphics Override Module
- Screensaver Control Module
* OpenSolaris 2008.05 Support
* FreeBSD / *BSD Support
* Improved Graph Rendering
* Result Analysis Option
* Improved Documentation

Phoronix Test Suite 1.0
======
#### Release Date: 5 June 2008 | Codename: Trondheim
Initial Stable Release
* 57 Test Profiles
* 23 Test Suites
* Download Caching Support
* XML-based Test / Suite System
* Support For Managing External Dependencies
* Basic Hardware, Software Detection Support
* Automated Test Installation
* Integrated Results Viewer
* Line, Bar, Boolean Graphing Support
* Batch Mode Support
* Global Test Upload Capability
* Support Across All Major Linux Distributions
