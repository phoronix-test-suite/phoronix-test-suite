#!/bin/sh

unzip -o stockfish-9-win.zip

echo "#!/bin/sh
cd stockfish-9-win/Windows
./stockfish_9_x64.exe bench 128 \$NUM_CPU_CORES 24 default depth > \$LOG_FILE 2>&1" > stockfish
chmod +x stockfish
