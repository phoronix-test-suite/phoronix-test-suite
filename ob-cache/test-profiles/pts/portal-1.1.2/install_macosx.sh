#!/bin/sh

unzip -o pts-portal-1.zip
mv pts-portal-1.dem $DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Portal/portal/

echo "#!/bin/sh
cd \$DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Portal/

./hl2.sh -game portal +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts-portal-1 -novid -fullscreen \$@" > portal
chmod +x portal
