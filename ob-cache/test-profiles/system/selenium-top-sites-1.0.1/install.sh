#!/bin/bash

unzip -o selenium-top-sites-2.zip

if which pip3>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Python pip3 is not found on the system! This test profile needs Python pip3 to proceed."
	echo 2 > ~/install-exit-status
fi

pip3 install --user selenium

# Drivers
tar -xf geckodriver-v0.24.0-linux64.tar.gz
unzip -o chromedriver_linux64.zip

# Script
echo "#!/bin/bash
rm -f run-benchmark.py
cp -f selenium-top-sites.py run-benchmark.py
sed -i \"s/Firefox/\$1/g\" run-benchmark.py

echo \"from selenium import webdriver
driver = webdriver.\$1()
if \\\"browserName\\\" in driver.capabilities:
	browserName = driver.capabilities['browserName']

if \\\"browserVersion\\\" in driver.capabilities:
	browserVersion = driver.capabilities['browserVersion']
else:
	browserVersion = driver.capabilities['version']

print('{0} {1}'.format(browserName, browserVersion))
driver.quit()\" > browser-version.py

PATH=\$HOME:\$PATH python3 ./run-benchmark.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

PATH=\$HOME:\$PATH python3 ./browser-version.py > ~/pts-footnote
" > selenium-top-sites

chmod +x selenium-top-sites
