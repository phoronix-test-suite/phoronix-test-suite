# Phoronix Test Suite 6.4.0m0
http://www.phoronix-test-suite.com/

The **Phoronix Test Suite** is the most comprehensive testing and benchmarking
platform available for Linux, Solaris, OS X, and BSD operating systems. The
Phoronix Test Suite allows for carrying out tests in a fully automated manner
from test installation to execution and reporting. All tests are meant to be
easily reproducible, easy-to-use, and support fully automated execution. The
Phoronix Test Suite is open-source under the GNU GPLv3 license and is developed
by Phoronix Media in cooperation with partners.

The Phoronix Test Suite itself is an open-source framework for conducting
automated tests along with reporting of test results, detection of installed
system software/hardware, and other features. Modules for the Phoronix Test
Suite also allow for integration with git-bisect and other revision control
systems for per-commit regression testing, system sensor monitoring, and other
extras.

This framework is designed to be an extensible architecture so that new test
profiles and suites can be easily added to represent performance benchmarks,
unit tests, and other quantitative and qualitative (e.g. image quality
comparison) measurements. Available through OpenBenchmarking.org, a
collaborative storage platform developed in conjunction with the Phoronix Test
Suite, are more than 200 individual test profiles and more than 60 test suites
available by default from the Phoronix Test Suite. Independent users are also
able to upload their test results, profiles, and suites to OpenBenchmarking.org.
A test profile is a single test that can be executed by the Phoronix Test Suite
-- with a series of options possible within every test -- and a test suite is a
seamless collection of test profiles and/or additional test suites. A test
profile consists of a set of Bash/shell scripts and XML files while a test suite
is a single XML file.

[OpenBenchmarking.org](http://www.openbenchmarking.org/) also allows for
conducting side-by-side result comparisons, a central location for storing and
sharing test results, and collaborating over test data.
[Phoromatic](http://www.phoromatic.com/) is a complementary platform to
OpenBenchmarking.org and the Phoronix Test Suite for interfacing with Phoronix
Test Suite client(s) to automatically execute test runs on a timed, per-commit,
or other trigger-driven basis. Phoromatic is designed for enterprise and allows
for the easy management of multiple networked systems running Phoronix Test
Suite clients via a single web-based interface.

Professional support and custom engineering for the Phoronix Test Suite,
Phoromatic, and OpenBenchmarking.org is available by contacting
<http://www.phoronix-test-suite.com/>.

Full details on the Phoronix Test Suite setup and usage is available from the
included HTML/PDF documentation within the phoronix-test-suite package and from
the Phoronix Test Suite website.

## Installation & Setup

The Phoronix Test Suite is supported on Linux, *BSD, Solaris, Mac OS X, and
Windows systems. However, the most full-featured and well supported operating
system for conducting the tests is Linux with some non-basic functionality not
being available under all platforms. The Phoronix Test Suite software/framework
is compatible with all major CPU architectures (e.g. i686, x86_64, ARM,
PowerPC), but not all of the test profiles/suites are compatible with all
architectures.

The Phoronix Test Suite can be installed for system-wide usage or run locally
without installation from the extracted tar.gz/zip package. The only hard
dependency on the Phoronix Test Suite is having command-line support for PHP
(PHP 5.3+) installed. A complete PHP stack (e.g. with web server) is **not**
needed, but merely the PHP command-line support, which is widely available from
operating system package managers under the name `php`, `php5-cli`, or `php5`.

## Usage

The process to download, install/setup, execute, and report the results of a
benchmark can be as simple as a command such as `phoronix-test-suite benchmark
smallpt` to run a simple CPU test profile. If wishing to simply install a test,
it's a matter of running `phoronix-test-suite install <test or suite name>` and
to run it's `phoronix-test-suite run <test or suite name>`. There's also a batch
mode for non-interactive benchmarking by first running `phoronix-test-suite
batch-setup` and then using the `batch-run` sub-command rather than `run`.

Viewing installed system hardware and software is available via
`phoronix-test-suite system-info` or `phoronix-test-suite detailed-system-info`
for greater verbosity.

Facilitating a result comparison from OpenBenchmarking.org can be done by
running, for example, `phoronix-test-suite benchmark 1204293-BY-PHORONIX357` if
wishing to compare the results of the
`http://openbenchmarking.org/result/1204293-BY-PHORONIX357` result file.

Additional information is available from the Phoronix Test Suite website
<http://www.phoronix-test-suite.com/> and the material bundled within the
`phoronix-test-suite/documentation/` directory. A man page is also bundled with
the phoronix-test-suite software.

