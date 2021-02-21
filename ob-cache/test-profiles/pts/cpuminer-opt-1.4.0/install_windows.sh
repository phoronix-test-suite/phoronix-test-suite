#!/bin/sh

unzip -o cpuminer-opt-3.15.5-windows.zip

echo "#!/bin/sh
./cpuminer-avx2.exe --quiet --no-color --time-limit=30 --threads=\$NUM_CPU_CORES --benchmark \$@ > \$LOG_FILE" > cpuminer-opt
chmod +x cpuminer-opt
