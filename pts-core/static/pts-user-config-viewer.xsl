<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - User Configuration File</title>
	</head>
	<body>
		<div style="width: 90%; margin: 20px auto 10px; text-align: left;">
			<p align="center"><img src="test-results/pts-results-viewer/pts-logo.png" /></p>
			<p>The <em>user-config.xml</em> file contains the user configuration options for the Phoronix Test Suite. To edit any option, open <em>user-config.xml</em> within your preferred text editor. For additional information, view the documentation included with the Phoronix Test Suite or visit <a href="http://www.phoronix-test-suite.com/">Phoronix-Test-Suite.com</a>.</p>

			<h1>Installation Options</h1>
			<h3>RemoveDownloadFiles: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Installation/RemoveDownloadFiles" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, once a test has been installed the downloaded files will be removed. Enabling this option will conserve disk space and in nearly all circumstances will not result in any problems. However, if a test profile directly depends upon a file that was downloaded (as opposed to something extracted from a downloaded file during the installation process), enabling this option will cause issues. If this option is set to <em>FALSE</em>, the downloaded files will not be removed unless the test is uninstalled. The default value is <em>FALSE</em>.</p>
			<h3>SearchMediaForCache: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Installation/SearchMediaForCache" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when installing a test it will automatically look for a Phoronix Test Suite download cache on removable media that is attached and mounted on the system. On the Linux operating system, the Phoronix Test Suite looks for devices mounted within the <em>/media/</em> directory. If a download cache is found (a <em>download-cache/</em> folder within the drive's root directory) and a file it is looking for with matching MD5 check-sum, the file will be automatically copied. Otherwise the standard download cache is checked. If this option is set to <em>FALSE</em>, removable media devices are not checked. The default value is <em>TRUE</em>.</p>
			<h3>SymLinkFilesFromCache: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Installation/SymLinkFilesFromCache" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, during the test installation process when a file is found in a Phoronix Test Suite download cache, instead of copying the file just provide a symbolic link to the file. Enabling this option will conserve disk space and in nearly all circumstances will not result in any issues, permitting the download cache files are always mounted during testing and are not located on removable media. If this option is set to <em>FALSE</em>, the files will be copied from the download cache. The default value is <em>FALSE</em>.</p>
			<h3>PromptForDownloadMirror: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Installation/PromptForDownloadMirror" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when downloading a test file the user will be prompted to select a mirror when multiple mirrors available. This option is targeted for those in remote regions or where their download speed may be greatly affected depending upon the server. If this option is set to <em>FALSE</em>, the Phoronix Test Suite will randomly pick a mirror. The default value is <em>FALSE</em>.</p>
			<h3>EnvironmentDirectory: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Installation/EnvironmentDirectory" /></span></h3>
			<p>This option sets the directory where tests will be installed to by the Phoronix Test Suite. The full path to the directory on the local file-system should be specified, though <em>~</em> is a valid character for denoting the user's home directory. The default value is <em>~/.phoronix-test-suite/installed-tests/</em>.</p>
			<h3>CacheDirectory: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Installation/CacheDirectory" /></span></h3>
			<p>This option sets the directory for the main download cache. The download cache is checked when installing a test while attempting to locate a needed test file. If the file is found in the download cache, it will not be downloaded from there instead of an Internet mirror. When running <em>phoronix-test-suite make-download-cache</em>, files are automatically copied to this directory. The full path to the directory should be specified, though <em>~</em> is a valid character for denoting the user's home directory. Specifying an HTTP or FTP URL is valid. The default value is <em>~/.phoronix-test-suite/download-cache/</em>.</p>

			<h1>Testing Options</h1>
			<h3>SleepTimeBetweenTests: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Testing/SleepTimeBetweenTests" /></span></h3>
			<p>This option sets the time (in seconds) to sleep between running tests. The default value is <em>8</em>.</p>
			<h3>SaveSystemDetails: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Testing/SaveSystemDetails" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when saving the results from a test it will also save various system details and logs to a sub-directory of the result file's location. Among the logs that will be archived include the X.Org log, dmesg, and lspci outputs. These system details may also be saved if a test suite explicitly requests this information be saved. If this option is set to <em>FALSE</em>, the system details / logs will not be saved by default. The default value is <em>FALSE</em>.</p>
			<h3>SaveBenchmarkLogs: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Testing/SaveBenchmarkLogs" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when saving the results from a test it will archive the complete output generated by the test -- instead of just the final result. The log(s) are then saved to a sub-directory of the result file's location. If this option is set to <em>FALSE</em>, the full test logs will not be saved. The default value is <em>FALSE</em>.</p>
			<h3>ResultsDirectory: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/Testing/ResultsDirectory" /></span></h3>
			<p>This option sets the directory where test results will be saved by the Phoronix Test Suite. The full path to the directory on the local file-system should be specified, though <em>~</em> is a valid character for denoting the user's home directory. The default value is <em>~/.phoronix-test-suite/test-results/</em>.</p>

			<h1>Batch Mode Options</h1>
			<p>The batch mode options are only used when using either the <em>batch-run</em> or <em>batch-benchmark</em> options with the Phoronix Test Suite. This mode is designed to fully automate the operation of the Phoronix Test Suite except for areas where the user would like to be prompted. To configure the batch mode options, it is recommended to run <em>phoronix-test-suite batch-setup</em> instead of modifying these values by hand.</p>
			<h3>SaveResults: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/BatchMode/SaveResults" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when running in batch mode the test results will be automatically saved.</p>
			<h3>OpenBrowser: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/BatchMode/OpenBrowser" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when running in batch mode the web-browser will automatically open when displaying test results. If this option is set to <em>FALSE</em>, the web-browser will not be opened.</p>
			<h3>UploadResults: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/BatchMode/UploadResults" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when running in batch mode the test results will be automatically uploaded to <a href="http://global.phoronix-test-suite.com/">Phoronix Global</a>.</p>
			<h3>PromptForTestIdentifier: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when running in batch mode the user will be prompted to enter a test identifier. If this option is set to <em>FALSE</em>, a test identifier will be automatically generated.</p>
			<h3>PromptForTestDescription: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/BatchMode/PromptForTestDescription" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when running in batch mode the user will be prompted to enter a test description. If this option is set to <em>FALSE</em>, the default test description will be used.</p>
			<h3>PromptSaveName: <span style="color: #CC0000;"><xsl:value-of select="PhoronixTestSuite/Options/BatchMode/PromptSaveName" /></span></h3>
			<p>If this option is set to <em>TRUE</em>, when running in batch mode the user will be prompted to enter a test name. If this option is set to <em>FALSE</em>, a test name will be automatically generated.</p>


		</div>
		<div style="text-align: center; font-size: 12px;">Copyright &#xA9; 2008 - 2009 by <a href="http://www.phoronix-media.com/" style="text-decoration: none; color: #000;">Phoronix Media</a>.</div>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
