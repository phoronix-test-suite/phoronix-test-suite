#!/bin/sh

unzip -o lc0-v0.28.0-windows-cpu-openblas.zip

echo "#!/bin/sh
./lc0.exe \$@ --threads=\$NUM_CPU_CORES -w b30e742bcfd905815e0e7dbd4e1bafb41ade748f85d006b8e28758f1a3107ae3 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
