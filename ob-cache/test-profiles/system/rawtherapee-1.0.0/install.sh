#!/bin/sh

if which rawtherapee-cli>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: RawTherapee is not found on the system! This test profile needs a working rawtherapee-cli in the PATH."
	echo 2 > ~/install-exit-status
fi

cd ~
echo "#!/bin/sh
rm -rf benchmarkRT
tar -xf benchmarkRT-2.tar.xz
cd benchmarkRT
RT_CLI=\`which rawtherapee-cli\`
RT_PATH=\`dirname \$RT_CLI\`
./benchmarkRT -e \$RT_PATH -a \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status
rawtherapee-cli --version > ~/pts-footnote 2>/dev/null" > rawtherapee
chmod +x rawtherapee
