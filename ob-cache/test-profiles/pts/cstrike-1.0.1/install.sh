#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/240
echo $? > ~/install-exit-status

unzip -o pts-cstrike-1.zip
mv pts-cstrike-1.dem $DEBUG_REAL_HOME/.steam/root/SteamApps/common/Counter-Strike\ Source/cstrike

echo "#!/bin/sh
cd \$DEBUG_REAL_HOME/.steam/root/SteamApps/common/Counter-Strike\ Source

LD_LIBRARY_PATH=\$DEBUG_REAL_HOME/.local/share/Steam/SteamApps/common/Counter-Strike\ Source/bin:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/i386/lib/i386-linux-gnu:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/i386/lib:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/i386/usr/lib/i386-linux-gnu:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/i386/usr/lib:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/amd64/lib/x86_64-linux-gnu:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/amd64/lib:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/amd64/usr/lib/x86_64-linux-gnu:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32/steam-runtime/amd64/usr/lib:/usr/lib32:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_32:\$DEBUG_REAL_HOME/.local/share/Steam/ubuntu12_64:\$DEBUG_REAL_HOME/.local/share/Steam/SteamApps/common/Counter-Strike\ Source:\$DEBUG_REAL_HOME/.local/share/Steam/SteamApps/common/Counter-Strike\ Source/bin ./hl2_linux -game cstrike +con_logfile \$LOG_FILE +cl_showfps 1 +timedemoquit pts-cstrike-1 -novid -fullscreen \$@" > cstrike
chmod +x cstrike
