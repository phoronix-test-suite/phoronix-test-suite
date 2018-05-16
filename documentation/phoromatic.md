
# Phoronix Test Suite Phoromatic

## Phoromatic Server

### Introduction
Phoromatic is a remote management system for the Phoronix Test Suite. Phoromatic allows the automatic (hence the name *Phoro-matic* ) scheduling of tests, remote installation of new tests, and the management of multiple test systems all through an intuitive, easy-to-use web interface. Tests can be scheduled to automatically run on a routine basis across multiple test systems. The test results are then available from this central, secure location.
Phoromatic was originally introduced with Phoronix Test Suite 2.0 via Phoromatic.com as a project going back to 2008~2009. Phoromatic.com debuted as a hosted instance with the option of behind-the-firewall licensing for use within organizations. With Phoronix Test Suite 5.2 the model shifted to offer a local, open-source version of Phoromatic built into the Phoronix Test Suite code-base. Thanks to continued enterprise development, with Phoronix Test Suite 5.4 is now a fully-functioning, built-in version of Phoromatic that's open-source and can be used for behind-the-firewall testing without needing to push results to OpenBenchmarking.org and the ability to keep all results private.
Phoromatic in Phoronix Test Suite 5.4 also has the ability to support zero-conf  network discovery using Avahi and the automatic distribution of needed test profiles/suites and test files. Phoronix Test Suite 5.4's Phoromatic is a significant breakthrough for open-source testing particularly those running this GPL benchmarking software within test labs and other large organizations.

### Features
Built atop the Phoronix Test Suite, Phoromatic offers many features for both enterprise and community/personal users:
#### Automated Scheduling
Whether it is every evening at 6:00PM, once every Thursday at 10:00AM or somewhere in between, Phoromatic can schedule tests to be run at user-defined intervals. The testing schedules can be updated through Phoromatic web interface. After the test(s) have run, the results will be immediately uploaded to Phoromatic.
#### Extensible
Any test profile or test suite that is compliant with the Phoronix Test Suite specification will work with Phoromatic. Phoromatic is able to leverage the hundreds of test profiles and test suites currently in the Phoronix Test Suite via OpenBenchmarking.org, along with any custom or proprietary test profiles you or your company utilize. Additionally, the Phoromatic interface allows the user to construct their own test suite(s).
#### Remote Testing
Once the test system is setup, all testing and management of that system can be done remotely. There is no need to execute Phoronix Test Suite commands locally using the GUI or command line version, but instead nearly all of the same features are accessible from the Phoromatic interface.
#### Multi-System Support
A single Phoromatic account is able to manage multiple test systems running the Phoronix Test Suite. Phoromatic supports grouping together test systems, tagging, and other features to support effectively managing many test systems. From the Phoromatic interface, installed system hardware and software from a given system can also be viewed.
#### Turn-Key Deployment
No additional software needs to be installed to support Phoromatic; all that's needed is Phoronix Test Suite 5.4 or later for full compatibility. New test systems can easily be synced with a given Phoromatic account by running a single command from the Phoronix Test Suite client.
#### Result Management
Test results are automatically uploaded to the Phoromatic account and remain private unless you opt to upload them to OpenBenchmarking.org. From the Phoromatic interface, results from multiple test systems can easily be compared and multiple results from the same systems can be used to track performance over time. There are also options to look at the statistical significance of the results and other features to efficiently and effectively analyze the system's performance.
#### Decentralized
Once the Phoronix Test Suite running on the Phoromatic Server has been able to cache all of the OpenBenchmarking.org test files and the needed files for each test, Phoromatic with any Phoronix Test Suite clients on your LAN can run fully decentralized without the need for a constant stream of OpenBenchmarking.org communication or Internet connection for that matter. (The only exception would be if your local systems don't have all their needed external dependencies and your system's package manager would need to install components like a compiler or necessary system libraries.
#### Fully Open-Source
Phoromatic is now fully open-source within the Phoronix Test Suite code-base for fostering greater development and new capabilities. Patches are welcome and Phoronix Media is available to provide commercial support and custom engineering services around Phoromatic and the Phoronix Test Suite.

### Phoromatic Server Setup
Phoromatic is built into the Phoronix Test Suite code-base and should be found in all packaged versions of the **phoronix-test-suite** . Starting the Phoromatic Server entails running phoronix-test-suite start-phoromatic-server after configuring the server information within *~/.phoronix-test-suite/user-config.xml* . The Phoromatic Server can with or without root permissions depending upon your firewall and the port numbers you wish to use for the server.
On the "client side", any up-to-date version of the Phoronix Test Suite can automatically communicate with the Phoromatic Server. If Avahi support is available (commonly in Linux distribution repositories as _avahi-tools_ ), there should be zero-conf discovery if the Phoromatic Server and client systems are on the same LAN. If a Phoronix Test Suite client discovers a Phoromatic Server, it will attempt to use it automatically as a local download cache. In the event of no Internet connection, it will also attempt to obtain the needed OpenBenchmarking.org test/suite meta-data from the Phoromatic Server based upon its archived meta-data. This allows the Phoronix Test Suite / Phoromatic deployment on the LAN to be self-sustaining without an Internet connection as long as the systems have all installed test dependencies.
Further configuration of the setup parameters for the Phoromatic Server and Phoronix Test Suite clients can be tuned via the *~/.phoronix-test-suite/user-config.xml* file. All control and configuration of the Phoromatic Server is done via the web-based interface when the Phoromatic Server is active.
The Phoromatic Server utilizes PHP/HHVM's built-in web-server capabilities and there's also a Phoronix Test Suite built-in WebSocket server that's also initiated for back-end processing. At this time there are no ports set by default for these services but must be defined within the user configuration file. With the Avahi zero-conf network discovery and other automated detection in place, there's little restrictions over the port selection.
Systemd and Upstart service files are shipped with the Phoronix Test Suite for those that wish to have the services automatically run as daemons. The only new requirements over the basic Phoronix Test Suite system requirements is having PHP-SQLite support installed and the newer version of PHP or HHVM is recommended for offering the best support.

### Example Deployments
#### Use Case A: Unrestricted Internet Access, Local Result Storage
Systems on your network with unrestricted Internet access is the easiest and simplest deployment for the Phoronix Test Suite and Phoromatic. After installing the Phoronix Test Suite on the system you wish to designate the Phoromatic Server and have configured the *user-config.xml* file, simply run:
**$ phoronix-test-suite start-phoromatic-server**
Assuming you have no firewall or permission issues, the built-in web server and WebSocket server should proceed to initiate along with outputting the IP/port information for these services. Unless otherwise disabled from the user configuration file and if avahi-tools is present, the Phoromatic Server will be advertised with Avahi for zero-configuration networking.
From the Phoromatic web interface you are able to create an account and from there proceed with the creating of test schedules, updating settings, and connecting systems. From the "client systems" you wish to use as the benchmarking nodes, it's simply a matter of running **phoronix-test-suite phoromatic.connect** with zero-conf networking or otherwise follow the information from the Phoromatic web interface for manual setup with the IP/port information.
#### Use Case B: No Internet Available To Client Systems
It's possible to run the Phoronix Test Suite and Phoromatic Server without a persistent Internet connection as long as you are able to first download the necessary files to the Phoromatic Server. After installing the Phoronix Test Suite on the system you wish to designate the Phoromatic Server and have configured the *user-config.xml* file, a few commands from the system while having an Internet connection will be able to cache the needed data:
**$ phoronix-test-suite make-download-cache x264 xonotic ffmpeg**
This command will simply download all of the needed test files for the tests/suites passed to the sub-command. Alternatively you could also pass pts/all to cache all tests. It's important though to just cache the tests/suites you'll be using on your network. This will generate the test file download cache by default to *~/.phoronix-test-suite/download-cache/* or */usr/share/phoronix-test-suite/download-cache/* depending upon your write permissions. You can always run this command later with more test files. Alternatively, if you already have a number of tests installed on the system, simply running "phoronix-test-suite make-download-cache" will generate the cache based upon the currently installed tests.
**$ phoronix-test-suite make-openbenchmarking-cache**
This command will cache as much of the OpenBenchmarking.org meta-data as possible for test profiles and test suites. After the above commands, the Phoromatic Server should no longer need a persistent Internet connection.
**$ phororonix-test-suite start-phoromatic-server**
Proceed to start the Phoromatic Server and operate as normal.
For the test clients without an Internet connection, as long as they're able to reach the Phoromatic Server, the Phoromatic Server should be able to automatically serve all of the needed test files download cache and OpenBenchmarking.org meta-data to the systems locally.
#### Use Case C: Phoromatic Across The Internet
If wishing to use the same Phoromatic Server across multiple geographic locations, it's easily possible -- you just lose out on the zero-conf networking ability. To let the Phoronix Test Suite client systems know about the remote Phoromatic Server, simply add the Phoromatic Server information to the client's *PhoromaticServers* element within the *user-config.xml* . Of course, make sure the Phoromatic Server has a globally resolvable IP address and its Phoromatic HTTP/WebSocket ports are open. Once informing the client of the Phoromatic Server, the use cases as above apply in the same manner.

### Client Setup
From Phoronix Test Suite client systems running on the LAN, the following command will report all available detected Phoromatic Servers along with important server and debugging information:
**$ phoronix-test-suite phoromatic.explore**
With the following example output on finding one successful server:
*IP: 192.168.1.211
HTTP PORT: 5447
WEBSOCKET PORT: 5427
SERVER: PHP 5.5.9-1ubuntu4.4 Development Server
PHORONIX TEST SUITE: Phoronix Test Suite v5.4.0m1 [5313]
DOWNLOAD CACHE: 19 FILES / 2390 MB CACHE SIZE
SUPPORTED OPENBENCHMARKING.ORG REPOSITORIES:
      pts - Last Generated: 05 Oct 2014 07:16*
Phoromatic Servers are detected by the Phoronix Test Suite through Avahi or if manually configuring the Phoronix Test Suite clients to point to Phoromatic Servers. For networks without Avahi/auto-discovery support or for test systems that may be connecting from another network, the IP address and HTTP port number can be added to the local system's *~/.phoronix-test-suite/user-config.xml* with the *PhoromaticServers* element. Adding the *IP:port* (the Phoromatic Server's HTTP port) to the PhoromaticServers *user-config.xml* element for will perform targeted probing by the Phoronix Test Suite without any dependence on Avahi. Multiple Phoromatic Servers can be added if each IP:port is delimited by a comma.
To connect a Phoronix Test Suite system for benchmarking to an account, log into your Phoromatic account from the web-interface and on the main/system pages will be instructions along with a specially formed string to run, e.g. *phoronix-test-suite phoromatic.connect 192.168.1.211:5447/I0SSJY* . When running that command once on the system(s) to be synced to that account, as the administrator you'll be able to validate/approve the systems from the Phoromatic web interface. After that, whenever the system(s) are to be running benchmarks, simply have the **phoronix-test-suite phoromatic.connect** command running on the system (after the initial account has been synced, simply running **phoronix-test-suite phoromatic.connect** is enough for the system to find the server and its account).

### Root Administrator
The root administrator account is able to manage the server-level settings, e.g. Phoromatic storage location and other global settings related to the Phoronix Test Suite / Phoromatic Server, from the web user-interface.
To enable the root administrator log-in, first from the server's command-line interface run **phoronix-test-suite phoromatic.set-root-admin-password** to set the password. Following that, you can log into the root administrator account via the web interface via the *rootadmin* user-name and the set password.

### Other Advice
#### Disable Internet Precaution
If you have an Internet connection but want to ensure your Phoronix Test Suite client doesn't attempt to use it for any matter, via the *~/.phoronix-test-suite/user-config.xml* you can set *NoInternetCommunication* to *TRUE* . There's also a NoNetworkCommunication tag, but setting that to TRUE will disable any form of network communication -- including communication with the Phoromatic Server.
#### Ports / Services
The Phoromatic Server process currently relies upon a PHP/HHVM built-in web server process and a PTS-hosted WebSocket server. The web server process handles the web UI and much of the responsibilities of the Phoromatic Server. Over time the PTS WebSocket server will be increasingly utilized for bi-directional, real-time communication between the server and clients -- including for features like viewing real-time hardware sensors of client systems from the server UI.
#### Systemd / Upstart
Packaged with the Phoronix Test Suite are basic *phoromatic-client* and *phoromatic-server* configurations for both Upstart and systemd init systems. The *phoromatic-server* configuration will launch the Phoronix Test Suite's Phoromatic Server and the *phoromatic-client* service will attempt to connect to a _pre-configured_ Phoromatic Server. The systemd service files will automatically be installed via the Phoronix Test Suite *install-sh* process while the Upstart jobs can be copied from *deploy/phoromatic-upstart/** to */etc/init* .
#### Cache Verification
To confirm the files accessible to Phoronix Test Suite client systems, from the Phoromatic Server web user-interface go to the *settings* page followed by the *cache settings* link to view information about the download and OpenBenchmarking.org caches. From the client systems, running **phoronix-test-suite phoromatic.explore** will also supply cache statistics.
#### Log Files
The Phoromatic Server will produce a log file of events / debugging information to *~/.phoronix-test-suite/phoromatic.log* or */var/log/phoromatic.log* depending upon the service's permissions. When running the Phoronix Test Suite Phoromatic client, the log will be written to one of the respective locations in *phoronix-test-suite.log* .
#### Multi-User Accounts
For each time a user account is made from the Phoromatic web UI's log-in page, all of the test schedules, systems, and other account information is separate to allow for a completely isolated multi-user system. If a main administrator (the one creating the account) wishes to have multiple users sharing the same account data, that user can create additional accounts from the *Users* tab of their account. The main administrator can make an additional administrator account or a "viewer" account that can consume the account's data but not create/modify the schedules, systems, or other account details.
#### File Locations
When running the Phoronix Test Suite Phoromatic Server as root, rather than using the *~/.phoronix-test-suite/* directory, the standard Linux file-system hierarchy standard is honored. The main storage path is */var/lib/phoronix-test-suite/* , the user configuration file is */etc/phoronix-test-suite.xml* , and */var/cache/phoronix-test-suite/* for cache files.
#### Uploading Other Test Results
Unscheduled test results and other results found on connected systems to a Phoromatic account can upload the data to the Phoromatic Server using the *phoronix-test-suite phoromatic.upload-result <result file identifier >* sub-command.
#### User Context File Logging
For those utilizing custom set context script files as part of the Phoromatic test schedule, any important notes / log information can be written to the file specified by the *PHOROMATIC_LOG_FILE* environment variable set while running the user context scripts. The contents of that file is then sent to the Phoromatic Server otherwise the standard output of the script's execution is submitted to the Phoromatic Server for logging. These logs can then be viewed by the Phoromatic Server along with the test results. Other environment variables accessible when running a user context script include *PHOROMATIC_TRIGGER* , *PHOROMATIC_SCHEDULE_ID* , and *PHOROMATIC_SCHEDULE_PROCESS* .
