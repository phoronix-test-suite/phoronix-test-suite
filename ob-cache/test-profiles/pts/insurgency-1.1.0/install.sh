#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/222880

unzip -o insurgency-pts1-dem.zip
mv insurgency-pts1.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/insurgency2/insurgency

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/insurgency2/

export __GL_THREADED_OPTIMIZATIONS=1

HOME=\$DEBUG_REAL_HOME ./insurgency_linux +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit insurgency-pts1 -console -novid -fullscreen \$@" > insurgency
chmod +x insurgency
