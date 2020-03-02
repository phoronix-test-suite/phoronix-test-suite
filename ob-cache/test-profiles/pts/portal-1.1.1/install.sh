#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/400
echo $? > ~/install-exit-status

unzip -o pts-portal-1.zip
mv pts-portal-1.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Portal/portal

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Portal

./hl2_linux -game portal +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts-portal-1 -novid -fullscreen \$@" > portal
chmod +x portal
