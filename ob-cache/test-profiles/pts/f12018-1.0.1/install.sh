#!/bin/sh

if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi

HOME=$DEBUG_REAL_HOME steam steam://install/737800

# This is needed since the game relies upon GPU settings contingent upon the deviceID in their XML file
echo "dxgi.customDeviceId = AAF0
dxgi.customVendorId = 1002
" > $DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2018/dxvk.conf


unzip -o f1-2018-configs-1.zip
cp -f *.xml $DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/737800/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2018/hardwaresettings/

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/737800/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2018/*.csv
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/737800/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2018/*.xml

echo \"<?xml version=\\\"1.0\\\" standalone=\\\"yes\\\" ?>
<config infinite_loop=\\\"false\\\" hardware_settings=\\\"\$1.xml\\\" season=\\\"2018\\\" show_fps=\\\"true\\\" >
  <track name=\\\"melbourne\\\" laps=\\\"1\\\" weather=\\\"clear\\\" num_cars=\\\"20\\\" camera_mode=\\\"cycle\\\" driver=\\\"sebastian_vettel\\\" grid_pos=\\\"1\\\" />
</config>\" > \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2018/benchmark/pts_benchmark.xml

HOME=\$DEBUG_REAL_HOME steam -applaunch 737800 -benchmark pts_benchmark.xml

sleep 30
while pgrep -x \"F1_2018.exe\" > /dev/null; do
    sleep 2
done
sleep 3
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/737800/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2018/*.xml | grep results | sed \"s/\\\"/ /g\" > \$LOG_FILE
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/737800/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/F1\ 2018/*.csv >> \$LOG_FILE" > f12018
chmod +x f12018
