#!/bin/sh
if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi
HOME=$DEBUG_REAL_HOME steam steam://install/271590
unzip -o gta5-settings-1.zip
# Fix
sed -i 's/MSAA value="0"/MSAA value="8"/g' settings_very_high.xml

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/271590/pfx/drive_c/users/steamuser/My\ Documents/Rockstar\ Games/GTA\ V/Benchmarks/*.txt
cp -f \$3 \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/271590/pfx/drive_c/users/steamuser/My\ Documents/Rockstar\ Games/GTA\ V/settings.xml
VULKAN_GPU=\`vulkaninfo | grep deviceName | head -n1 | cut -d \"=\" -f2 | xargs\`
sed -i \"s/UPDATE_GPU/\$VULKAN_GPU/g\" \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/271590/pfx/drive_c/users/steamuser/My\ Documents/Rockstar\ Games/GTA\ V/settings.xml
sed -i \"s/1920/\$1/g\" \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/271590/pfx/drive_c/users/steamuser/My\ Documents/Rockstar\ Games/GTA\ V/settings.xml
sed -i \"s/1080/\$2/g\" \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/271590/pfx/drive_c/users/steamuser/My\ Documents/Rockstar\ Games/GTA\ V/settings.xml
HOME=\$DEBUG_REAL_HOME steam -applaunch 271590 -frameLimit 0 -fullscreen -benchmark -width \$1 -height \$2
sleep 30
while pgrep -x \"GTA5.exe\" > /dev/null; do
    sleep 2
done
sleep 2
cat  \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/271590/pfx/drive_c/users/steamuser/My\ Documents/Rockstar\ Games/GTA\ V/Benchmarks/*.txt > \$LOG_FILE" > gta5
chmod +x gta5
