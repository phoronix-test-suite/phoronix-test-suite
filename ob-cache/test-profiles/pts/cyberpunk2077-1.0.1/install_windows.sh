#!/bin/sh
mkdir -p "$DEBUG_REAL_HOME\AppData\Local\CD Projekt Red\Cyberpunk 2077"
unzip -o cyperpunk2077-settings-1.zip
echo "#!/bin/bash
rm -f /cygdrive/c/Users/*/Documents/CD\ Projekt\ Red/Cyberpunk\ 2077/benchmarkResults/*/*
cp -f UserSettings_\${3}.json \"\$DEBUG_REAL_HOME\AppData\Local\CD Projekt Red\Cyberpunk 2077\UserSettings.json\"
sed -i \"s/3840x2160/\${1}x\${2}/g\"  \"\$DEBUG_REAL_HOME\AppData\Local\CD Projekt Red\Cyberpunk 2077\UserSettings.json\"
HOME=\$DEBUG_REAL_HOME  /cygdrive/c/Program\ Files\ \(x86\)/Steam/steamapps/common/Cyberpunk\ 2077/bin/x64/Cyberpunk2077.exe --launcher-skip -benchmark -skipStartScreen
sleep 1
cat /cygdrive/c/Users/*/Documents/CD\ Projekt\ Red/Cyberpunk\ 2077/benchmarkResults/*/summary.json > \$LOG_FILE" > cyberpunk2077
chmod +x cyberpunk2077