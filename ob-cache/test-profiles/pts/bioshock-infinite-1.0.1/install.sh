#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/8870

echo '#!/bin/bash
. steam-env-vars.sh
cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/BioShock\ Infinite
rm -rf $DEBUG_REAL_HOME/.local/share/irrationalgames/bioshockinfinite/GameDocuments/My\ Games/BioShock\ Infinite/Benchmarks/*.csv
./bioshock DefaultPCBenchmarkMap.xcmap -unattended $@
cat $DEBUG_REAL_HOME/.local/share/irrationalgames/bioshockinfinite/GameDocuments/My\ Games/BioShock\ Infinite/Benchmarks/*.csv > $LOG_FILE' > bioshock-infinite
chmod +x bioshock-infinite
