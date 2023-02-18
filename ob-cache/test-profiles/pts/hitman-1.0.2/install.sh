#!/bin/sh
HOME=$DEBUG_REAL_HOME steam steam://install/236870
echo "#!/bin/bash
killall -9 HitmanPro
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/HITMAN/VFS/User/hitman/profiledata*
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Hitman™/bin/
sleep 5
./HitmanPro \$@
cat \$DEBUG_REAL_HOME/.local/share/feral-interactive/HITMAN/VFS/User/hitman/profiledata.txt > \$LOG_FILE" > hitman
chmod +x hitman
