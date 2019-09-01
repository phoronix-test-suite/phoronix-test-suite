#!/bin/sh

mkdir $HOME/flac_
tar -xJf flac-1.3.2.tar.xz

cd flac-1.3.2/
./configure --prefix=$HOME/flac_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ~
rm -rf flac-1.3.2/
rm -rf flac_/share/

echo "#!/bin/sh
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
echo \$? > ~/test-exit-status" > encode-flac
chmod +x encode-flac
