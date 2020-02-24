#!/bin/sh

unzip -o lc0-v0.23.2-windows-cpu-openblas.zip

echo "#!/bin/sh
./lc0.exe \$@ --threads=\$NUM_CPU_CORES -w 9f44992aafe2f58e17d1d2565ba8e1ad6ae995f2d6be371a94b821221841f1d9 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
