#!/bin/sh
tar -xf sample-audio-long-1.tar.xz
tar -xf rnnoise-0.2.tar.gz
cd rnnoise-0.2
./autogen.sh
BUILD_OPTIONS=""
if [ $OS_TYPE = "Linux" ]
then
    if grep avx /proc/cpuinfo > /dev/null
    then
	BUILD_OPTIONS="--enable-x86-rtcd "
    fi
fi
./configure $BUILD_OPTIONS
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
./rnnoise-0.2/examples/rnnoise_demo  \$1 out.raw
echo \$? > ~/test-exit-status" > rnnoise
chmod +x rnnoise
