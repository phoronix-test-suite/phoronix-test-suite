#!/bin/bash

echo "#!/bin/sh
cd \"C:\Program Files\Indigo Benchmark\"
./Indigo\ Benchmark.exe \$@ > \$LOG_FILE" > indigobench
chmod +x indigobench

chmod +x IndigoBenchmark_v4.4.15_Setup.exe
/cygdrive/c/Windows/system32/cmd.exe /c IndigoBenchmark_v4.4.15_Setup.exe
