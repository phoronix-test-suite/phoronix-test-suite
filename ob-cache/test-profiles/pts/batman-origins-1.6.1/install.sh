#!/bin/sh

if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi

HOME=$DEBUG_REAL_HOME steam steam://install/209000

unzip -o batman-arkham-origins-config-1.zip
cp -f GFXSettings.BatmanArkhamOrigins.xml $DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/209000/pfx/drive_c/users/steamuser/My\ Documents/WB\ Games/Batman\ Arkham\ Origins/

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Batman\ Arkham\ Origins/SinglePlayer/BMGame/Logs/benchmark.log

cp -f GFXSettings.BatmanArkhamOrigins.xml \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/209000/pfx/drive_c/users/steamuser/My\ Documents/WB\ Games/Batman\ Arkham\ Origins/

HOME=\$DEBUG_REAL_HOME steam -applaunch 209000 Benchmark ResX=\$1 ResY=\$2
sleep 30
while [ ! -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Batman\ Arkham\ Origins/SinglePlayer/BMGame/Logs/benchmark.log ]
do
  sleep 2
done
killall -9 BatmanOrigins.e
killall -9 BatmanOrigins.exe
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Batman\ Arkham\ Origins/SinglePlayer/BMGame/Logs/benchmark.log > \$LOG_FILE" > batman-origins
chmod +x batman-origins
