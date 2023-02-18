#!/bin/sh
if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi
HOME=$DEBUG_REAL_HOME steam steam://install/1091500
mkdir -p $DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1091500/pfx/drive_c/users/steamuser/AppData/Local/CD\ Projekt\ Red/Cyberpunk\ 2077
unzip -o cyperpunk2077-settings-1.zip
echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1091500/pfx/drive_c/users/steamuser/Documents/CD\ Projekt\ Red/Cyberpunk\ 2077/benchmarkResults/*/*
cp -f UserSettings_\${3}.json \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1091500/pfx/drive_c/users/steamuser/AppData/Local/CD\ Projekt\ Red/Cyberpunk\ 2077/UserSettings.json
sed -i \"s/3840x2160/\${1}x\${2}/g\" \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1091500/pfx/drive_c/users/steamuser/AppData/Local/CD\ Projekt\ Red/Cyberpunk\ 2077/UserSettings.json
HOME=\$DEBUG_REAL_HOME steam -applaunch 1091500 --launcher-skip -benchmark -skipStartScreen
sleep 50
while pgrep -x \"GameThread\" > /dev/null; do
    sleep 2
done
sleep 3
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1091500/pfx/drive_c/users/steamuser/Documents/CD\ Projekt\ Red/Cyberpunk\ 2077/benchmarkResults/*/summary.json > \$LOG_FILE" > cyberpunk2077
chmod +x cyberpunk2077
