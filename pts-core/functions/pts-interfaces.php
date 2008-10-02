<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-interfaces.php: The XML interfaces for the Phoronix Test Suite to be used by the Phoronix tandem_Xml.

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

//
// TEST PROFILE SPECIFICATION
//

define("P_TEST_TITLE", "PhoronixTestSuite/TestInformation/Title"); // Test title shown to end-user
define("P_TEST_SUBTITLE", "PhoronixTestSuite/TestInformation/SubTitle"); // Subtitle
define("P_TEST_VERSION", "PhoronixTestSuite/TestInformation/Version"); // Test version
define("P_TEST_DESCRIPTION", "PhoronixTestSuite/TestInformation/Description"); // Test description
define("P_TEST_EXDEP", "PhoronixTestSuite/TestInformation/ExternalDependencies"); // Test description
define("P_TEST_SCALE", "PhoronixTestSuite/TestInformation/ResultScale"); // Result scale
define("P_TEST_QUANTIFIER", "PhoronixTestSuite/TestInformation/ResultQuantifier"); // Result quantifier
define("P_TEST_RESULTFORMAT", "PhoronixTestSuite/TestInformation/ResultFormat"); // Result format
define("P_TEST_PROPORTION", "PhoronixTestSuite/TestInformation/Proportion"); // Proportion
define("P_TEST_EXECUTABLE", "PhoronixTestSuite/TestInformation/Executable"); // Executable
define("P_TEST_RUNCOUNT", "PhoronixTestSuite/TestInformation/TimesToRun"); // Run count
define("P_TEST_IGNOREFIRSTRUN", "PhoronixTestSuite/TestInformation/IgnoreFirstRun"); // Ignore first run?
define("P_TEST_PREINSTALLMSG", "PhoronixTestSuite/TestInformation/PreInstallMessage"); // Pre-install message
define("P_TEST_POSTINSTALLMSG", "PhoronixTestSuite/TestInformation/PostInstallMessage"); // Post-install message
define("P_TEST_PRERUNMSG", "PhoronixTestSuite/TestInformation/PreRunMessage"); // Pre-run message
define("P_TEST_POSTRUNMSG", "PhoronixTestSuite/TestInformation/PostRunMessage"); // Post-run message

define("P_TEST_PTSVERSION", "PhoronixTestSuite/TestProfile/Version"); // PTS Test version
define("P_TEST_HARDWARE_TYPE", "PhoronixTestSuite/TestProfile/TestType"); // Test type
define("P_TEST_SOFTWARE_TYPE", "PhoronixTestSuite/TestProfile/SoftwareType"); // Test software type
define("P_TEST_MAINTAINER", "PhoronixTestSuite/TestProfile/Maintainer"); // Test software type
define("P_TEST_LICENSE", "PhoronixTestSuite/TestProfile/License"); // Test software license
define("P_TEST_STATUS", "PhoronixTestSuite/TestProfile/Status"); // The status of the test profile
define("P_TEST_SUPPORTEDARCHS", "PhoronixTestSuite/TestProfile/SupportedArchitectures"); // The system architectures supported by this test
define("P_TEST_SUPPORTEDPLATFORMS", "PhoronixTestSuite/TestProfile/SupportedPlatforms"); // The OS software platforms supported by this test
define("P_TEST_CTPEXTENDS", "PhoronixTestSuite/TestProfile/Extends"); // Does this test profile extend another test? (Cascading Test Profiles)
define("P_TEST_ROOTNEEDED", "PhoronixTestSuite/TestProfile/RequiresRoot"); // Is root access needed? (TODO: Implement RequiresRoot)
define("P_TEST_DOWNLOADSIZE", "PhoronixTestSuite/TestProfile/DownloadSize"); // Estimated size of capacity needed for downloads (in MB)
define("P_TEST_ENVIRONMENTSIZE", "PhoronixTestSuite/TestProfile/EnvironmentSize"); // Estimated size of capacity needed for testing environment (in MB)
define("P_TEST_ESTIMATEDTIME", "PhoronixTestSuite/TestProfile/EstimatedLength"); // Estimated length of time it takes the test to complete (in minutes)
define("P_TEST_PROJECTURL", "PhoronixTestSuite/TestProfile/ProjectURL"); // Estimated length of time it takes the test to complete (in minutes)

define("P_TEST_DEFAULTARGUMENTS", "PhoronixTestSuite/TestSettings/Default/Arguments"); // Default arguments
define("P_TEST_POSSIBLEPATHS", "PhoronixTestSuite/TestSettings/Default/PossiblePaths"); // Possible paths
define("P_TEST_OPTIONS_DISPLAYNAME", "PhoronixTestSuite/TestSettings/Option/DisplayName"); // The option names to show to the end-user
define("P_TEST_OPTIONS_ARGUMENTNAME", "PhoronixTestSuite/TestSettings/Option/ArgumentName"); // TODO: This option has been deprecated in Phoronix Test Suite 1.4. Switch to ArgumentPrefix instead.
define("P_TEST_OPTIONS_ARGPREFIX", "PhoronixTestSuite/TestSettings/Option/ArgumentPrefix"); // The option argument prefix
define("P_TEST_OPTIONS_ARGPOSTFIX", "PhoronixTestSuite/TestSettings/Option/ArgumentPostfix"); // The option argument postfix
define("P_TEST_OPTIONS_IDENTIFIER", "PhoronixTestSuite/TestSettings/Option/Identifier"); // Identifiers for each option
define("P_TEST_OPTIONS_MENU_GROUP", "PhoronixTestSuite/TestSettings/Option/Menu"); // XML group containing the menu options for the test
define("S_TEST_OPTIONS_MENU_GROUP_NAME", "Entry/Name"); // From inside the XML options menu group, the option name
define("S_TEST_OPTIONS_MENU_GROUP_VALUE", "Entry/Value"); // From inside the XML options menu group, the option value
define("P_TEST_OPTIONS_MENU_GROUP_NAME", P_TEST_OPTIONS_MENU_GROUP . "/" . S_TEST_OPTIONS_MENU_GROUP_NAME); // From inside the XML options menu group, the option name
define("P_TEST_OPTIONS_MENU_GROUP_VALUE", P_TEST_OPTIONS_MENU_GROUP . "/" . S_TEST_OPTIONS_MENU_GROUP_VALUE); // From inside the XML options menu group, the option value

//
// SELF-CONTAINED TEST PROFILE SPECIFICATION
//

if(IS_SCTP_MODE)
{
	define("P_TEST_SCTP_INSTALLSCRIPT", "PhoronixTestSuite/SelfContained/Installation"); // Installation routine
	define("P_TEST_SCTP_DOWNLOADS", "PhoronixTestSuite/SelfContained/Downloads"); // Downloads XML file
	define("P_TEST_SCTP_RESULTSPARSER", "PhoronixTestSuite/SelfContained/ResultsParser"); // Results Parser
	define("P_TEST_SCTP_PRERUN", "PhoronixTestSuite/SelfContained/PreRun"); // Pre-run script
	define("P_TEST_SCTP_POSTRUN", "PhoronixTestSuite/SelfContained/PostRun"); // Pre-run script
}

//
// TEST SUITE SPECIFICATION
//

define("P_SUITE_TITLE", "PhoronixTestSuite/SuiteInformation/Title"); // Suite title shown to end-user
define("P_SUITE_VERSION", "PhoronixTestSuite/SuiteInformation/Version"); // Suite version
define("P_SUITE_DESCRIPTION", "PhoronixTestSuite/SuiteInformation/Description"); // Test description
define("P_SUITE_MAINTAINER", "PhoronixTestSuite/SuiteInformation/Maintainer"); // Suite maintainer
define("P_SUITE_TYPE", "PhoronixTestSuite/SuiteInformation/TestType"); // Suite Type
define("P_SUITE_PRERUNMSG", "PhoronixTestSuite/SuiteInformation/PreRunMessage"); // Pre-run message
define("P_SUITE_POSTRUNMSG", "PhoronixTestSuite/SuiteInformation/PostRunMessage"); // Post-run message

define("P_SUITE_TEST_NAME", "PhoronixTestSuite/RunTest/Test"); // Names of tests in suite
define("P_SUITE_TEST_ARGUMENTS", "PhoronixTestSuite/RunTest/Arguments"); // Arguments of tests in suite
define("P_SUITE_TEST_DESCRIPTION", "PhoronixTestSuite/RunTest/Description"); // Description of tests in suite

//
// TEST DOWNLOAD SPECIFICATION
//

define("P_DOWNLOADS_PACKAGE_URL", "PhoronixTestSuite/Downloads/Package/URL"); // URL for PTS to download from
define("P_DOWNLOADS_PACKAGE_MD5", "PhoronixTestSuite/Downloads/Package/MD5"); // MD5 for PTS to verify
define("P_DOWNLOADS_PACKAGE_FILENAME", "PhoronixTestSuite/Downloads/Package/FileName"); // Local file-name for PTS to save package as
define("P_DOWNLOADS_PACKAGE_FILESIZE", "PhoronixTestSuite/Downloads/Package/FileSize"); // The size of the file to be downloaded (in bytes)
// DROPPED IN PTS 1.4, USE CTP INSTEAD: define("P_DOWNLOADS_PACKAGE_DESTINATION", "PhoronixTestSuite/Downloads/Package/DownloadTo"); // Location to save file to

//
// DOWNLOAD CACHE SPECIFICATION
//

define("P_CACHE_PACKAGE_FILENAME", "PhoronixTestSuite/DownloadCache/Package/FileName"); // Package file-name in download cache
define("P_CACHE_PACKAGE_MD5", "PhoronixTestSuite/DownloadCache/Package/MD5"); // Package MD5 in download cache
define("P_CACHE_PTS_VERSION", "PhoronixTestSuite/DownloadCache/PTS/Version"); // PTS version in download cache

//
// TEST EXTERNAL DEPENDENCY SPECIFICATION
//

define("P_EXDEP_PACKAGE_TITLE", "PhoronixTestSuite/ExternalDependencies/Package/Title"); // Title of external dependency package
define("P_EXDEP_PACKAGE_GENERIC", "PhoronixTestSuite/ExternalDependencies/Package/GenericName"); // Generic name of external dependency package
define("P_EXDEP_PACKAGE_SPECIFIC", "PhoronixTestSuite/ExternalDependencies/Package/PackageName"); // Specific package name of external dependency package
define("P_EXDEP_PACKAGE_FILECHECK", "PhoronixTestSuite/ExternalDependencies/Package/FileCheck"); // File check of external dependency package
define("P_EXDEP_PACKAGE_POSSIBLENAMES", "PhoronixTestSuite/ExternalDependencies/Package/PossibleNames"); // Possible names of external dependency package

//
// PTS RESULTS VIEWER SPECIFICATION
//

define("P_RESULTS_SYSTEM_HARDWARE", "PhoronixTestSuite/System/Hardware"); // System hardware in results
define("P_RESULTS_SYSTEM_SOFTWARE", "PhoronixTestSuite/System/Software"); // System software in results
define("P_RESULTS_SYSTEM_AUTHOR", "PhoronixTestSuite/System/Author"); // System user/author in results
define("P_RESULTS_SYSTEM_DATE", "PhoronixTestSuite/System/TestDate"); // System test date in results
define("P_RESULTS_SYSTEM_NOTES", "PhoronixTestSuite/System/TestNotes"); // System notes in results
define("P_RESULTS_SYSTEM_PTSVERSION", "PhoronixTestSuite/System/Version"); // System PTS version in results
define("P_RESULTS_SYSTEM_IDENTIFIERS", "PhoronixTestSuite/System/AssociatedIdentifiers"); // System PTS version in results

define("P_RESULTS_SUITE_TITLE", "PhoronixTestSuite/Suite/Title"); // Suite title shown to end-user
define("P_RESULTS_SUITE_NAME", "PhoronixTestSuite/Suite/Name"); // Real name of suite
define("P_RESULTS_SUITE_TYPE", "PhoronixTestSuite/Suite/Type"); // Type of suite
// DROPPED IN PTS 1.2: define("P_RESULTS_SUITE_MAINTAINER", "PhoronixTestSuite/Suite/Maintainer"); // Maintainer of suite
define("P_RESULTS_SUITE_VERSION", "PhoronixTestSuite/Suite/Version"); // Version of suite
define("P_RESULTS_SUITE_DESCRIPTION", "PhoronixTestSuite/Suite/Description"); // Description of suite
define("P_RESULTS_SUITE_EXTENSIONS", "PhoronixTestSuite/Suite/Extensions"); // Extensions of suite
define("P_RESULTS_SUITE_PROPERTIES", "PhoronixTestSuite/Suite/TestProperties"); // Properties during test execution

define("P_RESULTS_TEST_TESTNAME", "PhoronixTestSuite/Benchmark/TestName"); // Names of all tests in results
define("P_RESULTS_TEST_TITLE", "PhoronixTestSuite/Benchmark/Name"); // Title of all tests in results
define("P_RESULTS_TEST_SCALE", "PhoronixTestSuite/Benchmark/Scale"); // Scale of all tests in results
define("P_RESULTS_TEST_PROPORTION", "PhoronixTestSuite/Benchmark/Proportion"); // Proportion of all tests in results
define("P_RESULTS_TEST_RESULTFORMAT", "PhoronixTestSuite/Benchmark/ResultFormat"); // Result format of all tests in results
define("P_RESULTS_TEST_VERSION", "PhoronixTestSuite/Benchmark/Version"); // Versions of all tests in results
define("P_RESULTS_TEST_ARGUMENTS", "PhoronixTestSuite/Benchmark/TestArguments"); // Arguments of all tests in results
define("P_RESULTS_TEST_ATTRIBUTES", "PhoronixTestSuite/Benchmark/Attributes"); // Arguments of all tests in results
define("P_RESULTS_RESULTS_GROUP", "PhoronixTestSuite/Benchmark/Results"); // XML group containing the test identifiers and results
define("S_RESULTS_RESULTS_GROUP_IDENTIFIER", "Group/Entry/Identifier"); // From inside the XML results group, the results identifier
define("P_RESULTS_RESULTS_GROUP_IDENTIFIER", P_RESULTS_RESULTS_GROUP . "/" . S_RESULTS_RESULTS_GROUP_IDENTIFIER); // Full path to the results identifier
define("S_RESULTS_RESULTS_GROUP_VALUE", "Group/Entry/Value"); // From inside the XML results group, the results values
define("P_RESULTS_RESULTS_GROUP_VALUE", P_RESULTS_RESULTS_GROUP . "/" . S_RESULTS_RESULTS_GROUP_VALUE); // Full path to the results values

//
// USER CONFIGURATION SPECIFICATION
//

define("P_OPTION_GLOBAL_USERNAME", "PhoronixTestSuite/GlobalDatabase/UserName"); // Phoronix Global user-name
define("P_OPTION_GLOBAL_UPLOADKEY", "PhoronixTestSuite/GlobalDatabase/UploadKey"); // Phoronix Global upload key

define("P_OPTION_PROMPT_DOWNLOADLOC", "PhoronixTestSuite/Options/General/PromptForDownloadMirror"); // Results save directory
define("P_OPTION_LOAD_MODULES", "PhoronixTestSuite/Options/General/LoadModules"); // Modules to load by default

define("P_OPTION_TEST_SLEEPTIME", "PhoronixTestSuite/Options/Testing/SleepTimeBetweenTests"); // Time in seconds to sleep between tests
define("P_OPTION_LOG_VSYSDETAILS", "PhoronixTestSuite/Options/Testing/SaveSystemDetails"); // Log verbose system details?
define("P_OPTION_LOG_BENCHMARKFILES", "PhoronixTestSuite/Options/Testing/SaveBenchmarkLogs"); // Save benchmark logs?

define("P_OPTION_TEST_REMOVEDOWNLOADS", "PhoronixTestSuite/Options/Installation/RemoveDownloadFiles"); // Remove downloaded files after test is installed

define("P_OPTION_TEST_ENVIRONMENT", "PhoronixTestSuite/Options/FileManagement/EnvironmentDirectory"); // Results save directory
define("P_OPTION_CACHE_DIRECTORY", "PhoronixTestSuite/Options/FileManagement/CacheDirectory"); // Directory for reading/writing to download cache
define("P_OPTION_RESULTS_DIRECTORY", "PhoronixTestSuite/Options/FileManagement/ResultsDirectory"); // Results save directory

define("P_OPTION_BATCH_CONFIGURED", "PhoronixTestSuite/Options/BatchMode/Configured"); // Batch mode has been configured
define("P_OPTION_BATCH_SAVERESULTS", "PhoronixTestSuite/Options/BatchMode/SaveResults"); // Batch mode save results
define("P_OPTION_BATCH_LAUNCHBROWSER", "PhoronixTestSuite/Options/BatchMode/OpenBrowser"); // Batch mode open browser
define("P_OPTION_BATCH_UPLOADRESULTS", "PhoronixTestSuite/Options/BatchMode/UploadResults"); // Batch mode auto-upload to Phoronix Global
define("P_OPTION_BATCH_PROMPTIDENTIFIER", "PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier"); // Batch mode prompt for test identifier
define("P_OPTION_BATCH_PROMPTDESCRIPTION", "PhoronixTestSuite/Options/BatchMode/PromptForTestDescription"); // Batch mode prompt for test description
define("P_OPTION_BATCH_PROMPTSAVENAME", "PhoronixTestSuite/Options/BatchMode/PromptSaveName"); // Batch mode prompt for save results name

define("P_OPTION_TESTCORE_LASTVERSION", "PhoronixTestSuite/TestCore/LastRun/Version"); // Last version of the Phoronix Test Suite Run
define("P_OPTION_TESTCORE_LASTTIME", "PhoronixTestSuite/TestCore/LastRun/Time"); // Last time the Phoronix Test Suite ran
define("P_OPTION_USER_AGREEMENT", "PhoronixTestSuite/TestCore/UserInformation/AgreementCheckSum"); // PTS user agreement confirmation

//
// MODULE CONFIGURATION SPECIFICATION
//

define("P_MODULE_OPTION_NAME", "PhoronixTestSuite/Modules/Option/ModuleName"); // The name of the module
define("P_MODULE_OPTION_IDENTIFIER", "PhoronixTestSuite/Modules/Option/Identifier"); // The identifier of the option
define("P_MODULE_OPTION_VALUE", "PhoronixTestSuite/Modules/Option/Value"); // The value of the identifier for this module

//
// TEST INSTALLATION SPECIFICATION
//

define("P_INSTALL_TEST_NAME", "PhoronixTestSuite/TestInstallation/Environment/Name"); // Name of test
define("P_INSTALL_TEST_VERSION", "PhoronixTestSuite/TestInstallation/Environment/Version"); // PTS Version of test
define("P_INSTALL_TEST_CHECKSUM", "PhoronixTestSuite/TestInstallation/Environment/CheckSum"); // MD5 check-sum of executable
define("P_INSTALL_TEST_INSTALLTIME", "PhoronixTestSuite/TestInstallation/History/InstallTime"); // Time of test install
define("P_INSTALL_TEST_LASTRUNTIME", "PhoronixTestSuite/TestInstallation/History/LastRunTime"); // Time the test last run
define("P_INSTALL_TEST_TIMESRUN", "PhoronixTestSuite/TestInstallation/History/TimesRun"); // Time the test last run

//
// GRAPH CONFIGURATION SPECIFICATION
//

define("P_GRAPH_SIZE_WIDTH", "PhoronixTestSuite/Graphs/Size/Width"); // Graph width
define("P_GRAPH_SIZE_HEIGHT", "PhoronixTestSuite/Graphs/Size/Height"); // Graph height

define("P_GRAPH_COLOR_BACKGROUND", "PhoronixTestSuite/Graphs/Colors/Background"); // Graph color background
define("P_GRAPH_COLOR_BODY", "PhoronixTestSuite/Graphs/Colors/GraphBody"); // Graph color body
define("P_GRAPH_COLOR_BORDER", "PhoronixTestSuite/Graphs/Colors/Border"); // Graph color border
define("P_GRAPH_COLOR_ALTERNATE", "PhoronixTestSuite/Graphs/Colors/Alternate"); // Graph color alternate
define("P_GRAPH_COLOR_NOTCHES", "PhoronixTestSuite/Graphs/Colors/Notches"); // Graph color notches
define("P_GRAPH_COLOR_PAINT", "PhoronixTestSuite/Graphs/Colors/ObjectPaint"); // Graph color object paint
define("P_GRAPH_COLOR_TEXT", "PhoronixTestSuite/Graphs/Colors/Text"); // Graph color text
define("P_GRAPH_COLOR_BODYTEXT", "PhoronixTestSuite/Graphs/Colors/BodyText"); // Graph color body text
define("P_GRAPH_COLOR_HEADERS", "PhoronixTestSuite/Graphs/Colors/Headers"); // Graph color text headers
define("P_GRAPH_COLOR_MAINHEADERS", "PhoronixTestSuite/Graphs/Colors/MainHeaders"); // Graph color text main headers

define("P_GRAPH_FONT_TYPE", "PhoronixTestSuite/Graphs/Font/FontType"); // Graph font type
define("P_GRAPH_FONT_SIZE_HEADERS", "PhoronixTestSuite/Graphs/FontSize/Headers"); // Graph font size for headers
define("P_GRAPH_FONT_SIZE_SUBHEADERS", "PhoronixTestSuite/Graphs/FontSize/SubHeaders"); // Graph font size for sub-headers
define("P_GRAPH_FONT_SIZE_TEXT", "PhoronixTestSuite/Graphs/FontSize/ObjectText"); // Graph font size for object text
define("P_GRAPH_FONT_SIZE_IDENTIFIERS", "PhoronixTestSuite/Graphs/FontSize/Identifiers"); // Graph font size for identifiers
define("P_GRAPH_FONT_SIZE_AXIS", "PhoronixTestSuite/Graphs/FontSize/Axis"); // Graph font size for axis

define("P_GRAPH_RENDERBORDER", "PhoronixTestSuite/Graphs/Other/RenderBorder"); // Graph render border
define("P_GRAPH_MARKCOUNT", "PhoronixTestSuite/Graphs/Other/NumberOfMarks"); // Graph number of marks
define("P_GRAPH_WATERMARK", "PhoronixTestSuite/Graphs/Other/Watermark"); // Graph watermark
define("P_GRAPH_BORDER", "PhoronixTestSuite/Graphs/Other/Border"); // Graph border bool

?>
