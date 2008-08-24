#!/bin/sh

tar -xjf vdrift-2008-08-05-src.tar.bz2
cd vdrift-08-05-08/tools/
tar -xvf scons-local-0.96.95.tar.gz
cd ../bullet-2.66/
./configure
jam bulletcollision bulletmath
cd ..
./tools/scons.py

# TODO: Drop in benchmark.vdr to ~/.vdrift/replays/
# Config file at ~/.vdrift/VDrift.config

cd ..
echo "#!/bin/sh

cd vdrift-08-05-08/
./build/vdrift -benchmark" > vdrift
chmod +x vdrift

