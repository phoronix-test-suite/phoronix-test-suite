#!/bin/sh

unzip -o Crafty-25.2-Win64.zip

echo "#!/bin/sh
./crafty-win64.exe \$@ > \$LOG_FILE" > crafty-benchmark
chmod +x crafty-benchmark
