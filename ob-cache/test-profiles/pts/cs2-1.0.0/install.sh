#!/bin/sh
HOME=$DEBUG_REAL_HOME steam steam://install/730
unzip -o cs2-pts29.zip
mv pts29.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Counter-Strike\ Global\ Offensive/game/csgo
echo "#!/bin/bash
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Counter-Strike\ Global\ Offensive/game/csgo
echo \"\" > Source2Bench.csv
HOME=\$DEBUG_REAL_HOME steam -applaunch 730 \$@
sleep 15
tail -f  Source2Bench.csv | sed '/pts29/ q'
sleep 1
cat Source2Bench.csv >> \$LOG_FILE" > cs2
chmod +x cs2
