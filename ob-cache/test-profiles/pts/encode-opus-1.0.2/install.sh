#!/bin/sh

mkdir $HOME/opus-setup

tar -zxvf opus-1.0.1.tar.gz
cd opus-1.0.1/
./configure --prefix=$HOME/opus-setup
make -j $NUM_CPU_CORES
make install
cd ~

tar -zxvf opus-tools-0.1.5.tar.gz
cd opus-tools-0.1.5/
./configure --prefix=$HOME/opus-setup --with-opus=$HOME/opus-setup --with-opus-libraries=$HOME/opus-setup/lib --with-opus-includes=$HOME/opus-setup/include/opus
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~

rm -rf opus-1.0.1/
rm -rf opus-tools-0.1.5/

echo "#!/bin/sh
rm -f opus-sample.opus
LD_LIBRARY_PATH=\$HOME/opus-setup/lib:$LD_LIBRARY_PATH ./opus-setup/bin/opusenc \$TEST_EXTENDS/pts-trondheim.wav opus-sample.opus 2>&1
LD_LIBRARY_PATH=\$HOME/opus-setup/lib:$LD_LIBRARY_PATH ./opus-setup/bin/opusdec opus-sample.opus /dev/null 2>&1
echo \$? > ~/test-exit-status" > encode-opus
chmod +x encode-opus
