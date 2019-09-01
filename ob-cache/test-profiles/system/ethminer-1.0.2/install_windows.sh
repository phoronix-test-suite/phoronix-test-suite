#!/bin/sh

unzip -o ethminer-0.14.0.dev3-Windows.zip

echo "#!/bin/sh
cd bin
./ethminer.exe --benchmark-trial 10 --benchmark-trials 6 \$@ > \$LOG_FILE
./ethminer.exe -V | head -n 1 | cut -d ' ' -f 3- > ~/pts-test-version" > ethminer
chmod +x ethminer
