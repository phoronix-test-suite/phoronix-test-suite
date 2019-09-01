#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/203160
unzip -o tomb-raider-prefs-2.zip
mkdir -p $DEBUG_REAL_HOME/.local/share/feral-interactive/Tomb\ Raider
cp -f prefs-* $DEBUG_REAL_HOME/.local/share/feral-interactive/Tomb\ Raider

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.local/share/feral-interactive/Tomb\ Raider
cp -f \$3 preferences
sed -ie \"s/3840/\$1/g\" preferences
sed -ie \"s/2160/\$2/g\" preferences

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Tomb\ Raider/bin

rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/Tomb\ Raider/VFS/Local/benchmarks/*.txt

./TombRaider -benchmark

cat \$DEBUG_REAL_HOME/.local/share/feral-interactive/Tomb\ Raider/VFS/Local/benchmarks/*.txt > \$LOG_FILE
# rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/Tomb\ Raider/VFS/Local/benchmarks/*.txt" > tomb-raider
chmod +x tomb-raider
