#!/bin/sh

steam steam://install/730

unzip -o csgo-demo-10.zip
mv pts10.dem "C:\Program Files (x86)\Steam\steamapps\common\Counter-Strike Global Offensive\csgo"

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Steam\steamapps\common\Counter-Strike Global Offensive\"
rm -rf csgo/SourceBench*
rm -f UNKNOWN
./csgo.exe -game csgo \$@ +con_logfile log.log
cat csgo/log.log* > \$LOG_FILE
cat csgo/SourceBench* >> \$LOG_FILE
cat csgo/UNKNOWN >> \$LOG_FILE" > csgo
chmod +x csgo
