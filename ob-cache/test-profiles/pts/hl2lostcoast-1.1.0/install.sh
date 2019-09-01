#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/340
echo $? > ~/install-exit-status

unzip -o pts-lostcoast-2.zip
mv pts-lostcoast-2.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Half-Life\ 2/lostcoast

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Half-Life\ 2
./hl2_linux -game lostcoast +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts-lostcoast-2 -novid -fullscreen \$@" > hl2lostcoast
chmod +x hl2lostcoast
