#!/bin/sh
tar -xf libavif-1.0.4.tar.gz
cd libavif-1.0.4/ext
tar -xf ~/aom-350.tar.xz
mkdir aom/build.libavif
cd aom/build.libavif
export CXXFLAGS="-O3 -fPIC -Wno-error $CXXFLAGS"
export CFLAGS="-O3 -fPIC -Wno-error $CFLAGS"
cmake -G Ninja -DCMAKE_BUILD_TYPE=Release -DENABLE_DOCS=0 -DENABLE_EXAMPLES=0 -DENABLE_TESTDATA=0 -DENABLE_TESTS=0 -DENABLE_TOOLS=0 -DBUILD_SHARED_LIBS=1 ..
ninja
echo $? > ~/install-exit-status
cd ~/libavif-1.0.4/
mkdir build
cd build
cmake -DAVIF_CODEC_AOM=1 -DAVIF_LOCAL_AOM=1 -DAVIF_BUILD_APPS=1 ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./libavif-1.0.4/build/avifenc -j \$THREADCOUNT \$@
echo \$? > ~/test-exit-status" > avifenc
chmod +x avifenc
