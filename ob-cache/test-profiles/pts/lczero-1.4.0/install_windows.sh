#!/bin/sh

unzip -o lc0-v0.25.0-windows-cpu-openblas.zip

echo "#!/bin/sh
./lc0.exe \$@ --threads=\$NUM_CPU_CORES -w a7bbb6104419028cc720c8e2433c25f0b0f84a21b69a881b7dc7ffb35d7ddb69 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
