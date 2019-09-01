#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/310560
unzip -o dirt-rally-prefs-2.zip
mkdir -p $DEBUG_REAL_HOME/.local/share/feral-interactive/DiRT\ Rally
cp -f prefs-* $DEBUG_REAL_HOME/.local/share/feral-interactive/DiRT\ Rally

echo "#!/bin/bash
. steam-env-vars.sh
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/DiRT\ Rally/VFS/User/AppData/Roaming/My\ Games/DiRT\ Rally/benchmarks/*.xml
cd \$DEBUG_REAL_HOME/.local/share/feral-interactive/DiRT\ Rally
cp -f \$3 preferences
sed -ie \"s/3840/\$1/g\" preferences
sed -ie \"s/2160/\$2/g\" preferences

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/DiRT\ Rally/bin
./DirtRally -benchmark \$@
cat \$DEBUG_REAL_HOME/.local/share/feral-interactive/DiRT\ Rally/VFS/User/AppData/Roaming/My\ Games/DiRT\ Rally/benchmarks/*.xml | sed \"s/\\\"/ /g\" > \$LOG_FILE
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/DiRT\ Rally/VFS/User/AppData/Roaming/My\ Games/DiRT\ Rally/benchmarks/*.xml" > dirt-rally
chmod +x dirt-rally
