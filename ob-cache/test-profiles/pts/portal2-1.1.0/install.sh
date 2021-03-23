#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/620
echo $? > ~/install-exit-status

unzip -o portal2-demo-pts3.zip
mv pts3.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Portal\ 2/portal2

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Portal\ 2
xrandr -s \$2x\$4
sleep 2
./portal2_linux -game portal2 +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts3 -novid -mat_vsync 0 -fullscreen \$@
sleep 2
xrandr -s 0
sleep 2" > portal2
chmod +x portal2
