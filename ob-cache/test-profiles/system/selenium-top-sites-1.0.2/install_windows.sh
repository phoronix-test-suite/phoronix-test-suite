#!/bin/sh

unzip -o selenium-top-sites-2.zip

# Drivers
unzip -o geckodriver-v0.24.0-win64.zip
unzip -o chromedriver_win32_v74.zip

# Script
echo "#!/bin/sh
cmd /c \"\$DEBUG_REAL_HOME\AppData\Local\Programs\Python\Python37\Scripts\pip3.exe\" install --user selenium
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

cmd /c \"\$DEBUG_REAL_HOME\AppData\Local\Programs\Python\Python37\python.exe\"  ./run-benchmark.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

cmd /c \"\$DEBUG_REAL_HOME\AppData\Local\Programs\Python\Python37\python.exe\" ./browser-version.py > ~/pts-footnote
" > selenium-top-sites

chmod +x selenium-top-sites
