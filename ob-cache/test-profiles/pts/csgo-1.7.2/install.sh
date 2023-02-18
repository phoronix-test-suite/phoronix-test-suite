#!/bin/sh
HOME=$DEBUG_REAL_HOME steam steam://install/730
unzip -o csgo-demo-10.zip
mv pts10.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Counter-Strike\ Global\ Offensive/csgo

echo "#!/bin/bash
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Counter-Strike\ Global\ Offensive/
rm -rf csgo/SourceBench*
rm -f UNKNOWN
rm -f csgo/UNKNOWN
rm -f csgo/log.log
HOME=\$DEBUG_REAL_HOME steam -applaunch 730 \$@ +con_logfile log.log

# CSGO with Vulkan at least sometimes is hanging on exit...
sleep 15
tail -f  csgo/log.log | sed '/Host_Shutdown/ q'
sleep 1
killall -9 csgo_linux64

cat csgo/log.log* > \$LOG_FILE
cat csgo/SourceBench* >> \$LOG_FILE
cat csgo/UNKNOWN >> \$LOG_FILE" > csgo
chmod +x csgo
