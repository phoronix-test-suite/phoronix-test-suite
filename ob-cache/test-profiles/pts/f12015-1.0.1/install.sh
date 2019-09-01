#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/286570

tar -xjvf F12015-prefs-1.tar.bz2

echo "#!/bin/bash
HOME=\$DEBUG_REAL_HOME 
. steam-env-vars.sh
cp -f \$@ \$HOME/.local/share/feral-interactive/F1\ 2015/preferences
cp -f \$@ \$HOME/.local/share/feral-interactive/F1\ 2015/preferences1
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1\ 2015/bin
rm -f \$HOME/.local/share/feral-interactive/F1\ 2015/VFS/User/AppData/Roaming/My\ Games/F1\ 2015/Benchmark*.xml
echo \$@ > \$HOME/.local/share/feral-interactive/F1\ 2015/preferences2
./F12015 -benchmark > \$LOG_FILE
cat \$HOME/.local/share/feral-interactive/F1\ 2015/VFS/User/AppData/Roaming/My\ Games/F1\ 2015/Benchmark*.xml | sed \"s/\\\"/ /g\"  > \$LOG_FILE" > f12015
chmod +x f12015
