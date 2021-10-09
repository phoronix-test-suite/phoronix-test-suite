#!/bin/bash

if which pip3>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Python pip3 is not found on the system! This test profile needs Python pip3 to proceed."
	echo 2 > ~/install-exit-status
fi

pip3 install --user selenium

unzip -o selenium-scripts-9.zip

# Drivers
tar -xf geckodriver-v0.29.1-linux64.tar.gz
unzip -o chromedriver_linux64_v94.zip

sed -i 's,https://krakenbenchmark.mozilla.org/,https://mozilla.github.io/krakenbenchmark.mozilla.org/index.html,g' selenium-run-kraken.py

# Script
echo "#!/bin/bash
rm -f run-benchmark.py
cp -f selenium-run-\$1.py run-benchmark.py
sed -i \"s/Firefox/\$2/g\" run-benchmark.py

echo \"from selenium import webdriver
driver = webdriver.\$2()
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
" > selenium

chmod +x selenium
