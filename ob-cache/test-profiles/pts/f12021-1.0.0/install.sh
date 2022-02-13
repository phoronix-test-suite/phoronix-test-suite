#!/bin/sh

if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi

HOME=$DEBUG_REAL_HOME steam steam://install/1134570

# This is needed since the game relies upon GPU settings contingent upon the deviceID in their XML file
echo "dxgi.customDeviceId = 73BF
dxgi.customVendorId = 1002
" > $DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2021/dxvk.conf

unzip -o f1-2021-proton-prefs-1.zip
cp -f *.xml $DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1134570/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2021/hardwaresettings/

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1134570/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2021/benchmark/*.csv
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1134570/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2021/benchmark/*.xml

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1134570/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2021/hardwaresettings/
cp -f \$3 hardware_settings_config.xml

sed -ie \"s/1920/\$1/g\" hardware_settings_config.xml
sed -ie \"s/1080/\$2/g\" hardware_settings_config.xml

# Without the CPU string matching, settings fail to apply
THIS_CPU=\`cat /proc/cpuinfo | grep \"model name\" | cut -d \":\" -f2 | tail -n1 | xargs\`
sed -ie \"s/AMD Ryzen 9 5950X 16-Core Processor/\$THIS_CPU/g\" hardware_settings_config.xml

HOME=\$DEBUG_REAL_HOME steam -applaunch 1134570 -benchmark example_benchmark.xml

sleep 30
while pgrep -x \"F1_2021_dx12.ex\" > /dev/null; do
    sleep 2
done
sleep 3
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1134570/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2021/benchmark/*.xml | grep results | sed \"s/\\\"/ /g\" > \$LOG_FILE
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/1134570/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2021/benchmark/*.csv >> \$LOG_FILE" > f12021
chmod +x f12021
