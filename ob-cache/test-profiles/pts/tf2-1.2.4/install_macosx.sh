#!/bin/sh

# HOME=$DEBUG_REAL_HOME steam steam://install/440

unzip -o pts4-tf2-aug15.zip
mv pts4.dem $DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Team\ Fortress\ 2/tf/

echo "#!/bin/sh
cd \$DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Team\ Fortress\ 2/
HOME=\$DEBUG_REAL_HOME ./hl2.sh -game tf +con_logfile log.log +cl_showfps 1 -fullscreen -novid +timedemoquit pts4 \$@
cat tf/log.log* > \$LOG_FILE" > tf2
chmod +x tf2
