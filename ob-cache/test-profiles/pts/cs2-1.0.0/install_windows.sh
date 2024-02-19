#!/bin/sh
steam steam://install/730
unzip -o cs2-pts29.zip
mv pts29.dem "C:\Program Files (x86)\Steam\steamapps\common\Counter-Strike Global Offensive\csgo\game\csgo"
echo "#!/bin/sh
cd \"C:\Program Files (x86)\Steam\steamapps\common\Counter-Strike Global Offensive\game\csgo\"
rm -rf csgo/SourceBench*
rm -f UNKNOWN
./csgo.exe -game csgo \$@ +con_logfile log.log
cat csgo/log.log* > \$LOG_FILE
cat csgo/SourceBench* >> \$LOG_FILE
cat csgo/UNKNOWN >> \$LOG_FILE" > csgo
chmod +x csgo
