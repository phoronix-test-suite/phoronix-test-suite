#!/bin/sh

unzip -o lc0-v0.22.0-windows-blas.zip

cp -f 0cf3fafcbd18e17d11d75d669d8dbf38eb89a57fbf0202196834433629da65ae weights
echo "#!/bin/sh
./lc0.exe \$@ --threads=\$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
