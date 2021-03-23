#!/bin/sh

unzip -o portal2-demo-pts3.zip
mv pts3.dem $DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Portal\ 2/portal2/

echo "#!/bin/sh
cd \$DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Portal\ 2/

./portal2.sh -game portal2 +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts3 -novid -mat_vsync 0 -fullscreen \$@" > portal2
chmod +x portal2
