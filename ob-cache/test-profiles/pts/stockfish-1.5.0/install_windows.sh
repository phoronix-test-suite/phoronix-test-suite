#!/bin/sh
unzip -o stockfish-windows-x86-64-avx2.zip
mv stockfish stockfish_16_1_win_x64_avx2
echo "#!/bin/sh
cd stockfish_16_1_win_x64_avx2
./stockfish-windows-x86-64-avx2.exe bench 4096 \$NUM_CPU_CORES 26 default depth > \$LOG_FILE 2>&1" > stockfish
chmod +x stockfish

