#!/bin/sh

if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi

HOME=$DEBUG_REAL_HOME steam steam://install/1080110

# This is needed since the game relies upon GPU settings contingent upon the deviceID in their XML file
echo "dxgi.customDeviceId = AAF0
dxgi.customVendorId = 1002
" > $DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2020/dxvk.conf

unzip -o f1-2020-proton-prefs-1.zip
cp -f *.xml $DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1080110/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2020/hardwaresettings/
cp -f pts_benchmark.xml \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2020/benchmark/pts_benchmark.xml

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1080110/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2020/benchmark/*.csv
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1080110/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2020/benchmark/*.xml

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1080110/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2020/hardwaresettings/
cp -f \$3 hardware_settings_config.xml

sed -ie \"s/3840/\$1/g\" hardware_settings_config.xml
sed -ie \"s/2160/\$2/g\" hardware_settings_config.xml

cp -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2020/F1_2020.exe \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2020/F1_2020_dx12.exe

PROTON_USE_SECCOMP=1 HOME=\$DEBUG_REAL_HOME steam -applaunch 1080110 -benchmark pts_benchmark.xml -force-d3d11

sleep 30
while pgrep -x \"F1_2020_dx12.ex\" > /dev/null; do
    sleep 2
done
sleep 3
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1080110/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2020/benchmark/*.xml | grep results | sed \"s/\\\"/ /g\" > \$LOG_FILE
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1080110/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2020/benchmark/*.csv >> \$LOG_FILE" > f12020
chmod +x f12020
