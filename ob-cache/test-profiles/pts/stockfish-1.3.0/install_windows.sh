#!/bin/sh

unzip -o stockfish_13_win_x64_avx2.zip

echo "#!/bin/sh
cd stockfish_13_win_x64_avx2
./stockfish_13_win_x64_avx2.exe bench 128 \$NUM_CPU_CORES 24 default depth > \$LOG_FILE 2>&1" > stockfish
chmod +x stockfish
