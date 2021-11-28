#!/bin/sh

unzip -o hl2-ep2-pts1-demo.zip
mv pts1.dem $DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Half-Life\ 2/ep2/

echo "#!/bin/sh
cd \$DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Half-Life\ 2/

./hl2.sh -game ep2 -steam +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts1 -novid -mat_vsync 0 -fullscreen \$@" > hl2-ep2
chmod +x hl2-ep2
