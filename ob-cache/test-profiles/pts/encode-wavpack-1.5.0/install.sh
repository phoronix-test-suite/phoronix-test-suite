#!/bin/sh
tar -xf WavPack-5.7.0.tar.gz
cd WavPack-5.7.0
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>12?12:\$NUM_CPU_CORES))
./WavPack-5.7.0/build/wavpack --threads=\$THREADCOUNT -q -r -hhx3 -o out.wv large-wav-audio-file-speech-sample.wav 2>&1
echo \$? > ~/test-exit-status" > encode-wavpack
chmod +x encode-wavpack
