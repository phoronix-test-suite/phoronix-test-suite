<?php

// Phoronix Test Suite - XML Interfaces

define("P_TEST_TITLE", "PhoronixTestSuite/TestInformation/Title"); // Test title shown to end-user
define("P_TEST_DESCRIPTION", "PhoronixTestSuite/TestInformation/Description"); // Test description
define("P_TEST_EXDEP", "PhoronixTestSuite/TestInformation/ExternalDependencies"); // Test description
define("P_TEST_VERSION", "PhoronixTestSuite/TestInformation/Version"); // Test version
define("P_TEST_SCALE", "PhoronixTestSuite/TestInformation/ResultScale"); // Result scale
define("P_TEST_PROPORTION", "PhoronixTestSuite/TestInformation/Proportion"); // Proportion
define("P_TEST_PTSVERSION", "PhoronixTestSuite/TestProfile/Version"); // PTS Test version
define("P_TEST_HARDWARE_TYPE", "PhoronixTestSuite/TestProfile/TestType"); // Test type
define("P_TEST_SOFTWARE_TYPE", "PhoronixTestSuite/TestProfile/SoftwareType"); // Test software type
define("P_TEST_MAINTAINER", "PhoronixTestSuite/TestProfile/Maintainer"); // Test software type
define("P_TEST_LICENSE", "PhoronixTestSuite/TestProfile/License"); // Test software license
define("P_TEST_STATUS", "PhoronixTestSuite/TestProfile/Status"); // The status of the test profile
define("P_TEST_DEFAULTARGUMENTS", "PhoronixTestSuite/TestSettings/Default/Arguments"); // Default arguments
define("P_TEST_SUBTITLE", "PhoronixTestSuite/TestInformation/SubTitle"); // Subtitle
define("P_TEST_EXECUTABLE", "PhoronixTestSuite/TestInformation/Executable"); // Executable
define("P_TEST_RUNCOUNT", "PhoronixTestSuite/TestInformation/TimesToRun"); // Run count
define("P_TEST_IGNOREFIRSTRUN", "PhoronixTestSuite/TestInformation/IgnoreFirstRun"); // Ignore first run?
define("P_TEST_PRERUNMSG", "PhoronixTestSuite/TestInformation/PreRunMessage"); // Pre-run message
define("P_TEST_POSSIBLEPATHS", "PhoronixTestSuite/TestSettings/Default/PossiblePaths"); // Possible paths

define("P_TEST_OPTIONS_DISPLAYNAME", "PhoronixTestSuite/TestSettings/Option/DisplayName"); // The option names to show to the end-user
define("P_TEST_OPTIONS_ARGUMENTNAME", "PhoronixTestSuite/TestSettings/Option/ArgumentName"); // The option argument names
define("P_TEST_OPTIONS_IDENTIFIER", "PhoronixTestSuite/TestSettings/Option/Identifier"); // Identifiers for each option
define("P_TEST_OPTIONS_MENU_GROUP", "PhoronixTestSuite/TestSettings/Option/Menu"); // XML group containing the menu options for the test
define("S_TEST_OPTIONS_MENU_GROUP_NAME", "Entry/Name"); // From inside the XML options menu group, the option name
define("S_TEST_OPTIONS_MENU_GROUP_VALUE", "Entry/Value"); // From inside the XML options menu group, the option value
define("P_TEST_OPTIONS_MENU_GROUP_NAME", P_TEST_OPTIONS_MENU_GROUP . "/" . S_TEST_OPTIONS_MENU_GROUP_NAME); // From inside the XML options menu group, the option name
define("P_TEST_OPTIONS_MENU_GROUP_VALUE", P_TEST_OPTIONS_MENU_GROUP . "/" . S_TEST_OPTIONS_MENU_GROUP_VALUE); // From inside the XML options menu group, the option value

define("P_SUITE_TITLE", "PhoronixTestSuite/SuiteInformation/Title"); // Suite title shown to end-user
define("P_SUITE_DESCRIPTION", "PhoronixTestSuite/SuiteInformation/Description"); // Test description
define("P_SUITE_TYPE", "PhoronixTestSuite/SuiteInformation/TestType"); // Suite Type
define("P_SUITE_MAINTAINER", "PhoronixTestSuite/SuiteInformation/Maintainer"); // Suite maintainer
define("P_SUITE_VERSION", "PhoronixTestSuite/SuiteInformation/Version"); // Suite version
define("P_SUITE_TEST_NAME", "PhoronixTestSuite/RunTest/Test"); // Names of tests in suite
define("P_SUITE_TEST_ARGUMENTS", "PhoronixTestSuite/RunTest/Arguments"); // Arguments of tests in suite
define("P_SUITE_TEST_DESCRIPTION", "PhoronixTestSuite/RunTest/Description"); // Description of tests in suite

define("P_OPTION_GLOBAL_USERNAME", "PhoronixTestSuite/GlobalDatabase/UserName"); // PTS Global user-name
define("P_OPTION_GLOBAL_UPLOADKEY", "PhoronixTestSuite/GlobalDatabase/UploadKey"); // PTS Global upload key
define("P_OPTION_TEST_SCREENSAVER", "PhoronixTestSuite/Options/Benchmarking/ToggleScreensaver"); // Toggle screensaver?
define("P_OPTION_TEST_SLEEPTIME", "PhoronixTestSuite/Options/Benchmarking/SleepTimeBetweenTests"); // Time in seconds to sleep between tests
define("P_OPTION_TEST_ENVIRONMENT", "PhoronixTestSuite/Options/Benchmarking/EnvironmentDirectory"); // Results save directory
define("P_OPTION_RESULTS_DIRECTORY", "PhoronixTestSuite/Options/Results/Directory"); // Results save directory
define("P_OPTION_BATCH_SAVERESULTS", "PhoronixTestSuite/Options/BatchMode/SaveResults"); // Batch mode save results
define("P_OPTION_BATCH_LAUNCHBROWSER", "PhoronixTestSuite/Options/BatchMode/OpenBrowser"); // Batch mode open browser
define("P_OPTION_BATCH_UPLOADRESULTS", "PhoronixTestSuite/Options/BatchMode/UploadResults"); // Batch mode auto-upload to PTS Global
define("P_OPTION_BATCH_PROMPTIDENTIFIER", "PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier"); // Batch mode prompt for test identifier
define("P_OPTION_USER_AGREEMENT", "PhoronixTestSuite/Trondheim/UserAgreement"); // PTS user agreement confirmation

define("P_DOWNLOADS_PACKAGE_URL", "PhoronixTestSuite/Downloads/Package/URL"); // URL for PTS to download from
define("P_DOWNLOADS_PACKAGE_MD5", "PhoronixTestSuite/Downloads/Package/MD5"); // MD5 for PTS to verify
define("P_DOWNLOADS_PACKAGE_FILENAME", "PhoronixTestSuite/Downloads/Package/FileName"); // Local file-name for PTS to save package as
define("P_DOWNLOADS_PACKAGE_DESTINATION", "PhoronixTestSuite/Downloads/Package/DownloadTo"); // Location to save file to

define("P_EXDEP_PACKAGE_TITLE", "PhoronixTestSuite/ExternalDependencies/Package/Title"); // Title of external dependency package
define("P_EXDEP_PACKAGE_GENERIC", "PhoronixTestSuite/ExternalDependencies/Package/GenericName"); // Generic name of external dependency package
define("P_EXDEP_PACKAGE_SPECIFIC", "PhoronixTestSuite/ExternalDependencies/Package/PackageName"); // Specific package name of external dependency package
define("P_EXDEP_PACKAGE_FILECHECK", "PhoronixTestSuite/ExternalDependencies/Package/FileCheck"); // File check of external dependency package
define("P_EXDEP_PACKAGE_POSSIBLENAMES", "PhoronixTestSuite/ExternalDependencies/Package/PossibleNames"); // Possible names of external dependency package

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
define("P_RESULTS_SUITE_MAINTAINER", "PhoronixTestSuite/Suite/Maintainer"); // Maintainer of suite
define("P_RESULTS_SUITE_VERSION", "PhoronixTestSuite/Suite/Version"); // Version of suite
define("P_RESULTS_SUITE_DESCRIPTION", "PhoronixTestSuite/Suite/Description"); // Description of suite
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

?>
