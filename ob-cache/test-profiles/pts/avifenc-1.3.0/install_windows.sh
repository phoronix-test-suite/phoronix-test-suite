#!/bin/sh
unzip -o avifenc-011-windows.zip
chmod +x avifenc-011.exe
echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./avifenc-010.exe -j \$THREADCOUNT \$@" > avifenc
chmod +x avifenc
