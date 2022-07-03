#!/bin/sh

unzip -o avifenc-010-windows.zip
mv avifenc.exe avifenc-010.exe
chmod +x avifenc-010.exe

echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./avifenc-010.exe -j \$THREADCOUNT \$@" > avifenc
chmod +x avifenc
