#!/bin/sh

chmod +x avifenc-090.exe
unzip -o sample-photo-6000x4000-1.zip

echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./avifenc-090.exe -j \$THREADCOUNT \$@" > avifenc
chmod +x avifenc
