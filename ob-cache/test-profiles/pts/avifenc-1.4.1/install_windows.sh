#!/bin/sh
unzip -o libavif-v1.0.4-avifenc-avifdec-windows.zip
chmod +x avifenc.exe
echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./avifenc.exe -j \$THREADCOUNT \$@" > avifenc
chmod +x avifenc
