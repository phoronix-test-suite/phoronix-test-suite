#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/222880

unzip -o insurgency_pts_2021-1.zip
mv insurgency_pts_2021.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/insurgency2/insurgency

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/insurgency2/

export __GL_THREADED_OPTIMIZATIONS=1

HOME=\$DEBUG_REAL_HOME ./insurgency_linux +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit insurgency_pts_2021.dem -console -novid -fullscreen \$@ &
sleep 15
tail -f \$LOG_FILE | sed '/variability/ q'
sleep 8
killall -9 insurgency_linu
killall -9 insurgency.sh
sleep 3" > insurgency
chmod +x insurgency
