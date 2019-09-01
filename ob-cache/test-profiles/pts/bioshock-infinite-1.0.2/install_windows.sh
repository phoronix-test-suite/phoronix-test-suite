#!/bin/sh

steam steam://install/8870

echo '#!/bin/bash
cd "C:\Program Files (x86)\Steam\steamapps\common\BioShock Infinite\Binaries\Win32"
rm -f /cygdrive/c/Users/*/Documents/My\ Games/BioShock\ Infinite/Benchmarks/*.csv
./BioShockInfinite.exe DefaultPCBenchmarkMap.xcmap -unattended $@
sleep 150
cat /cygdrive/c/Users/*/Documents/My\ Games/BioShock\ Infinite/Benchmarks/*.csv > $LOG_FILE' > bioshock-infinite
chmod +x bioshock-infinite
