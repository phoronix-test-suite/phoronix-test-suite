#!/bin/sh

if which ethminer>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: ethminer is not found on the system! This test profile needs a working ethminer installation in PATH"
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
ethminer --benchmark-trial 10 -M 0 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
ethminer -V | head -n 1 | cut -d ' ' -f 3- > ~/pts-test-version 2>/dev/null " > ethminer
chmod +x ethminer
