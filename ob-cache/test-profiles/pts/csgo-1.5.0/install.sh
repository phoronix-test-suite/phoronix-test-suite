#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/730

unzip -o csgo-demo-6.zip
mv pts6.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Counter-Strike\ Global\ Offensive/csgo

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Counter-Strike\ Global\ Offensive/
./csgo_linux64 -game csgo +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts6 -novid -fullscreen \$@" > csgo
chmod +x csgo
