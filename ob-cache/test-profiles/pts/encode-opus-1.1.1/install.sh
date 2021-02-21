#!/bin/sh

mkdir $HOME/opus-setup

tar -xf opus-1.3.1.tar.gz
cd opus-1.3.1/
./configure --prefix=$HOME/opus-setup
make -j $NUM_CPU_CORES
make install
cd ~

export PKG_CONFIG_PATH=$HOME/opus-setup/lib/pkgconfig:$PKG_CONFIG_PATH

tar -xf opusfile-0.12.tar.gz
cd opusfile-0.12
./configure --prefix=$HOME/opus-setup
make -j $NUM_CPU_CORES
make install
cd ~

tar -xf libopusenc-0.2.tar.gz
cd libopusenc-0.2
./configure --prefix=$HOME/opus-setup
make -j $NUM_CPU_CORES
make install
cd ~

tar -xf flac-1.3.3.tar.xz
cd flac-1.3.3
./configure --prefix=$HOME/opus-setup
make -j $NUM_CPU_CORES
make install
cd ~

tar -xf opus-tools-0.2.tar.gz
cd opus-tools-0.2/
./configure --prefix=$HOME/opus-setup --with-opus=$HOME/opus-setup --with-opus-libraries=$HOME/opus-setup/lib --with-opus-includes=$HOME/opus-setup/include/opus
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~

rm -rf opus-1.3.1/
rm -rf opus-tools-0.2/
rm -rf opusfile-0.12

echo "#!/bin/sh
LD_LIBRARY_PATH=\$HOME/opus-setup/lib:$LD_LIBRARY_PATH ./opus-setup/bin/opusenc 2L38_01_192kHz.flac opus-sample.opus 2>&1
echo \$? > ~/test-exit-status" > encode-opus
chmod +x encode-opus
