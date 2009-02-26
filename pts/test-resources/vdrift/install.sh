#!/bin/sh

tar -xjf vdrift-2009-02-15-src.tar.bz2
cd vdrift-2009-02-15/
tar -xvf bullet-2.73-sp1.tgz
cd bullet-2.73/
./autogen.sh
./configure
make
cd ..
scons

# TODO: Drop in benchmark.vdr to ~/.vdrift/replays/
# Config file at ~/.vdrift/VDrift.config

cd ..
echo "#!/bin/sh

cd vdrift-2009-02-15/
./build/vdrift -benchmark > \$LOG_FILE 2>&1" > vdrift
chmod +x vdrift

