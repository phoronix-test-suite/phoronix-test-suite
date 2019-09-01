#!/bin/sh

unzip -o dow3-prefs-2.zip

HOME=$DEBUG_REAL_HOME steam steam://install/285190
mkdir -p $DEBUG_REAL_HOME/.local/share/feral-interactive/Dawn\ of\ War\ III

echo "#!/bin/bash
GAME_PREFS=\"\$DEBUG_REAL_HOME/.local/share/feral-interactive/Dawn of War III\"
. steam-env-vars.sh

mv \"\$GAME_PREFS/\" \"\$GAME_PREFS.pts-bkp\"
mkdir -p \"\$GAME_PREFS\"

cp -f \"preferences.\$3.xml\" \"\$GAME_PREFS/preferences\"

cd \"\$GAME_PREFS\" || exit
sed -ie \"s/1920/\$1/g\" preferences
sed -ie \"s/1080/\$2/g\" preferences

if [ \"X\$4\" = \"XVULKAN\" ]
then
	sed -i  's/<value name=\"UseVulkan\" type=\"integer\">0<\/value>/<value name=\"UseVulkan\" type=\"integer\">1<\/value>/' preferences
	
fi

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Dawn\ of\ War\ III/ || exit
./DawnOfWar3.sh
cd \"\$GAME_PREFS/VFS/User/AppData/Roaming/My Games/Dawn of War III/LogFiles/\"
cat perfreport*.csv  > \"\$LOG_FILE\"

rm -rf \"\$GAME_PREFS/\"
mv \"\$GAME_PREFS.pts-bkp/\" \"\$GAME_PREFS\"" > dow3
chmod +x dow3
