#!/bin/sh

if which gmic>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: G'MIC is not found on the system! This test profile needs a working gmic installation in the PATH"
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
gmic \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status
gmic --version 2>&1 | grep Version > ~/pts-footnote " > gmic-run
chmod +x gmic-run
