#!/bin/sh

if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi

HOME=$DEBUG_REAL_HOME steam steam://install/690790

# This is needed since the game relies upon GPU settings contingent upon the deviceID in their XML file
echo "dxgi.customDeviceId = AAF0
dxgi.customVendorId = 1002
" > $DEBUG_REAL_HOME/.steam/steam/steamapps/common/DiRT\ Rally\ 2.0/dxvk.conf


unzip -o dirt-rally-20-proton-prefs-2.zip
cp -f *.xml $DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/690790/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/DiRT\ Rally\ 2.0/hardwaresettings/

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/690790/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/DiRT\ Rally\ 2.0/benchmarks/*.xml 

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/690790/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/DiRT\ Rally\ 2.0/hardwaresettings/
cp -f \$3 hardware_settings_config.xml

sed -ie \"s/3840/\$1/g\" hardware_settings_config.xml
sed -ie \"s/1600/\$2/g\" hardware_settings_config.xml

HOME=\$DEBUG_REAL_HOME steam -applaunch 690790 -benchmark 

sleep 30
while pgrep -x \"dirtrally2.exe\" > /dev/null; do
    sleep 2
done
sleep 3
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/690790/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/DiRT\ Rally\ 2.0/benchmarks/*.xml | sed \"s/\\\"/ /g\" > \$LOG_FILE" > dirt-rally2
chmod +x dirt-rally2
