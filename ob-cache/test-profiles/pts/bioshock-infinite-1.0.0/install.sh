#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/8870

echo '#!/bin/sh
cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/BioShock\ Infinite
rm -rf $DEBUG_REAL_HOME/.local/share/irrationalgames/bioshockinfinite/GameDocuments/My\ Games/BioShock\ Infinite/Benchmarks/*.csv
HOME=$DEBUG_REAL_HOME LD_LIBRARY_PATH=$DEBUG_REAL_HOME/.steam/ubuntu12_32:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/lib/i386-linux-gnu:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/lib:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/usr/lib/i386-linux-gnu:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/usr/lib:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/lib/x86_64-linux-gnu:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/lib:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/usr/lib/x86_64-linux-gnu:$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/usr/lib:/usr/lib32:/usr/lib32:$DEBUG_REAL_HOME/.steam/ubuntu12_32:$DEBUG_REAL_HOME/.steam/ubuntu12_64:$DEBUG_REAL_HOME/.steam/steam/steamapps/common/BioShock\ Infinite:$DEBUG_REAL_HOME/.steam/steam/steamapps/common/BioShock\ Infinite/bin ./bioshock DefaultPCBenchmarkMap.xcmap -unattended $@
cat $DEBUG_REAL_HOME/.local/share/irrationalgames/bioshockinfinite/GameDocuments/My\ Games/BioShock\ Infinite/Benchmarks/*.csv > $LOG_FILE' > bioshock-infinite
chmod +x bioshock-infinite
