#!/bin/sh
HOME=$DEBUG_REAL_HOME steam steam://install/2014780
cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/X-Plane\ 12
echo "2014780" > steam_appid.txt
cd ~
echo "#!/bin/sh
#Workaround for 11.50 beta fail
rm -f \$DEBUG_REAL_HOME/.local/share/vulkan/implicit_layer.d/steam*.json
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/X-Plane\ 12
HOME=\$DEBUG_REAL_HOME ./X-Plane-x86_64 \$@
sed -i 's/FRAMERATE TEST/\nFRAMERATE TEST/g' Log.txt
mv Log.txt \$LOG_FILE" > xplane12
chmod +x xplane12
