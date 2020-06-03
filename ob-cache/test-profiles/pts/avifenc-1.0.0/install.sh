#!/bin/sh

tar -xf libavif-0.7.3.tar.gz
cd libavif-0.7.3/ext
tar -xf ../../aom-200.tar.xz
mkdir aom/build.libavif
cd aom/build.libavif

export CXXFLAGS="-O3 -fPIC"
export CFLAGS="-O3 -fPIC"

cmake -G Ninja -DCMAKE_BUILD_TYPE=Release -DENABLE_DOCS=0 -DENABLE_EXAMPLES=0 -DENABLE_TESTDATA=0 -DENABLE_TESTS=0 -DENABLE_TOOLS=0 ..
ninja
echo $? > ~/install-exit-status

cd ~/libavif-0.7.3/
mkdir build
cd build
cmake -DAVIF_CODEC_AOM=1 -DAVIF_LOCAL_AOM=1 -DAVIF_BUILD_APPS=1 ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
unzip -o sample-photo-6000x4000-1.zip

echo "#!/bin/sh
./libavif-0.7.3/build/avifenc -j \$NUM_CPU_CORES \$@
echo \$? > ~/test-exit-status
" > avifenc
chmod +x avifenc
