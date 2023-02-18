#!/bin/sh
unzip -o cpuminer-opt-3.20.3-windows.zip
echo "#!/bin/sh
./cpuminer-avx2.exe --quiet --no-color --time-limit=30 --threads=\$NUM_CPU_CORES --benchmark \$@  | grep Benchmark > \$LOG_FILE" > cpuminer-opt
chmod +x cpuminer-opt
