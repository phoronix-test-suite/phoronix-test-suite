#!/bin/sh

open IndigoBenchmark_v4.4.15.pkg

echo "#!/bin/sh
cd /Applications/Indigo\ Benchmark.app/Contents/MacOS/
./indigo_benchmark \$@ > \$LOG_FILE" > indigobench
chmod +x indigobench
