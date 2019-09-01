#!/bin/sh

if which octave-cli >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Octave (octave-cli) is not found on the system!"
	echo 2 > ~/install-exit-status
fi

tar -xf octave-benchmark-1.1.1.tar.gz
#cd benchmark-1.1.1
#./configure
#make

cd ~
echo "#!/bin/sh
cd benchmark-1.1.1/inst
for filename in benchmark_*.m; do
        octave-cli \$filename >> \$LOG_FILE
done
echo \$? > ~/test-exit-status

octave-cli --version | head -n 1 | cut -d \" \" -f 4 > ~/pts-test-version 2>/dev/null" > octave-benchmark
chmod +x octave-benchmark


