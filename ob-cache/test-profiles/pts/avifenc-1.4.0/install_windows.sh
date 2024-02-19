#!/bin/sh
chmod +x avifenc-10.exe
echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./avifenc-10.exe -j \$THREADCOUNT \$@" > avifenc
chmod +x avifenc
