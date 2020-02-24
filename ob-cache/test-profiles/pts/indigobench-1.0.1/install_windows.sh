#!/bin/sh

echo "#!/bin/sh
cd \"C:\Program Files\Indigo Benchmark\"
./Indigo\ Benchmark.exe \$@ > \$LOG_FILE" > indigobench
chmod +x indigobench

./IndigoBenchmark_x64_v4.0.64_Setup.exe

