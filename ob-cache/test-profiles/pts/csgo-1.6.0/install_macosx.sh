#!/bin/sh

# steam steam://install/730

unzip -o csgo-demo-10.zip
mv pts10.dem $DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Counter-Strike\ Global\ Offensive/csgo/

echo "#!/bin/sh

cd \$DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/Counter-Strike\ Global\ Offensive/
rm -rf csgo/SourceBench*
rm -f UNKNOWN
HOME=\$DEBUG_REAL_HOME ./csgo.sh -game csgo \$@
cat csgo/SourceBench* > \$LOG_FILE
cat csgo/UNKNOWN >> \$LOG_FILE" > csgo
chmod +x csgo
