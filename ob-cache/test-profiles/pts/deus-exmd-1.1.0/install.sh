#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/337000

unzip -o deusex-preferences-2.zip

echo "#!/bin/bash
killall -9 DeusExMD
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/Deus\ Ex\ Mankind\ Divided/VFS/User/AppData/Roaming/Deus\ Ex\ -\ Mankind\ Divided/*.txt
. steam-env-vars.sh
cat \$1.xml > \$DEBUG_REAL_HOME/.local/share/feral-interactive/Deus\ Ex\ Mankind\ Divided/preferences
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Deus\ Ex\ Mankind\ Divided/bin
sleep 4
./DeusExMD -benchmark
cat \$DEBUG_REAL_HOME/.local/share/feral-interactive/Deus\ Ex\ Mankind\ Divided/VFS/User/AppData/Roaming/Deus\ Ex\ -\ Mankind\ Divided/*.txt > \$LOG_FILE" > deus-exmd
chmod +x deus-exmd
