#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/420
echo $? > ~/install-exit-status

unzip -o hl2-ep2-pts1-demo.zip
mv pts1.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Half-Life\ 2/ep2

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Half-Life\ 2
xrandr -s \$2x\$4
sleep 2
./hl2_linux -game ep2 -steam +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts1 -novid -mat_vsync 0 -fullscreen \$@
sleep 2
xrandr -s 0
sleep 2" > hl2-ep2
chmod +x hl2-ep2
