<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - Test Installation: <xsl:value-of select="PhoronixTestSuite/TestInstallation/Environment/Name" /></title>
	</head>
	<body>
		<div style="width: 90%; margin: 20px auto 10px; text-align: left;">
			<h1><xsl:value-of select="PhoronixTestSuite/TestInstallation/Environment/Name" /></h1>
			<p><strong>Installed Version:</strong> <xsl:value-of select="PhoronixTestSuite/TestInstallation/Environment/Version" /></p>
			<p><strong>Install Time:</strong> <xsl:value-of select="PhoronixTestSuite/TestInstallation/History/InstallTime" /></p>
			<p><strong>Installation Time:</strong> <xsl:value-of select="PhoronixTestSuite/TestInstallation/History/InstallTimeLength" /> (seconds)</p>
			<p><strong>Last Time Run:</strong> <xsl:value-of select="PhoronixTestSuite/TestInstallation/History/LastRunTime" /></p>
			<p><strong>Total Times Run:</strong> <xsl:value-of select="PhoronixTestSuite/TestInstallation/History/TimesRun" /></p>
			<p><strong>Average Run Time:</strong> <xsl:value-of select="PhoronixTestSuite/TestInstallation/History/AverageRunTime" /> (seconds)</p>
			<p><strong>Most Recent Run Time:</strong> <xsl:value-of select="PhoronixTestSuite/TestInstallation/History/LatestRunTime" /> (seconds)</p>
		</div>
		<div style="text-align: center; font-size: 12px; font-style: italic;">The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a> is a product of <a href="http://www.phoronix-media.com/">Phoronix Media</a>.</div>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
