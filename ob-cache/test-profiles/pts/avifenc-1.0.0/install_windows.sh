#!/bin/sh

chmod +x avifenc-073.exe
unzip -o sample-photo-6000x4000-1.zip

echo "#!/bin/sh
./avifenc-073.exe -j \$NUM_CPU_CORES \$@" > avifenc
chmod +x avifenc
