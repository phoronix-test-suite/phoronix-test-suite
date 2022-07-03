#!/bin/sh

unzip -o stockfish_15_win_x64_avx2.zip
chmod +x stockfish_15_win_x64_avx2/stockfish_15_x64_avx2.exe

echo "#!/bin/sh
cd stockfish_15_win_x64_avx2/
./stockfish_15_x64_avx2.exe bench 128 \$NUM_CPU_CORES 24 default depth > \$LOG_FILE 2>&1" > stockfish
chmod +x stockfish
