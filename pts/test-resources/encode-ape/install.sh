#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/ape_

tar -xvf mac-3.99-u4-b5-s4.tar.gz
cd mac-3.99-u4-b5-s4/
CXXFLAGS="-DSHNTOOL" ./configure --prefix=$THIS_DIR/ape_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf mac-3.99-u4-b5-s4/

echo "#!/bin/sh
./ape_/bin/mac \$TEST_EXTENDS/pts-trondheim-3.wav /dev/null -c4000 1>/dev/null 2>/dev/null
exit 0" > ape_process
chmod +x ape_process

echo "#!/bin/sh
/usr/bin/time -f \"WAV To APE Encode Time: %e Seconds\" ./ape_process 2>&1" > encode-ape
chmod +x encode-ape
