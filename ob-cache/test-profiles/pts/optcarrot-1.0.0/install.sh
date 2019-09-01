#!/bin/sh

tar -xf ocarrot-20180404.tar.xz

if which ruby>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Ruby is not found on the system! This test profile depends upon Ruby support."
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
cd optcarrot-master
ruby -v -Ilib -r./tools/shim bin/optcarrot --benchmark --opt examples/Lan_Master.nes > \$LOG_FILE 2>&1
ruby -v > ~/pts-footnote" > optcarrot
chmod +x optcarrot
