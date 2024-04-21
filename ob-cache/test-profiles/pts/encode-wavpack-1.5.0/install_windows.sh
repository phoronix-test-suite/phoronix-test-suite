#!/bin/sh
unzip -o wavpack-5.7.0-x64.zip
chmod +x wavpack.exe
echo "#!/bin/bash
THREADCOUNT=\$((\$NUM_CPU_CORES>12?12:\$NUM_CPU_CORES))
./wavpack.exe --threads=\$THREADCOUNT -q -r -hhx3 -y pts-trondheim.wav
echo \$? > ~/test-exit-status" > encode-wavpack
chmod +x encode-wavpack
