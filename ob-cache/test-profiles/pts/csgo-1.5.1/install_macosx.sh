#!/bin/sh

# steam steam://install/730

unzip -o csgo-demo-6.zip
mv pts6.dem $DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Counter-Strike\ Global\ Offensive/csgo/

echo "#!/bin/sh

cd \$DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Counter-Strike\ Global\ Offensive/
HOME=\$DEBUG_REAL_HOME ./csgo.sh -game csgo +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts6 -novid -fullscreen \$@" > csgo
chmod +x csgo
