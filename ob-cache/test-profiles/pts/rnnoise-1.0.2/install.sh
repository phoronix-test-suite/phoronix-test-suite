#!/bin/sh

tar -xf sample-audio-long-1.tar.xz
tar -xf rnnoise-20200628.tar.xz

rm -rf rnnoise-git
mv rnnoise rnnoise-git
cd rnnoise-git
./autogen.sh
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd rnnoise-git
./examples/rnnoise_demo  ../sample-audio-long.raw out.raw
echo \$? > ~/test-exit-status" > rnnoise
chmod +x rnnoise
