#!/bin/sh

mkdir $HOME/ape_

tar -zxvf mac-3.99-u4-b5-s6.tar.gz
cd mac-3.99-u4-b5-s6/
CXXFLAGS="-DSHNTOOL" ./configure --prefix=$HOME/ape_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf mac-3.99-u4-b5-s6/

echo "#!/bin/sh
./ape_/bin/mac \$TEST_EXTENDS/pts-trondheim.wav /dev/null -c4000 > \$LOG_FILE 2>&1" > encode-ape
chmod +x encode-ape
