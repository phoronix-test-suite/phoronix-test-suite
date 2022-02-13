#!/bin/sh

if which gnuradio-config-info>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: GNU Radio is not found on the system! This test profile is checking for a gnuradio-config-info command in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

tar -xf gnuradio_benchmark-1.tar.xz

volk_profile

echo "#!/bin/sh
python3 gnuradio_benchmark.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
gnuradio-config-info -v > ~/pts-footnote 2>/dev/null" > gnuradio
chmod +x gnuradio
