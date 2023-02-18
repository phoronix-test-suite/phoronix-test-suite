#!/bin/sh
mkdir $HOME/flac_
tar -xJf flac-1.4.2.tar.xz

cd flac-1.4.2
./configure --prefix=$HOME/flac_
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
else
	make -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
fi
make install

cd ~
rm -rf flac-1.4.2
rm -rf flac_/share/
echo "#!/bin/sh
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
./flac_/bin/flac --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output 2>&1
echo \$? > ~/test-exit-status" > encode-flac
chmod +x encode-flac
