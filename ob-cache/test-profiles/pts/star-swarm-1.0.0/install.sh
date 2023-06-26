#!/bin/sh
if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi
HOME=$DEBUG_REAL_HOME steam steam://install/267130
echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/267130/pfx/drive_c/users/steamuser/Documents/Star\ Swarm/Output_*.txt

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Star\ Swarm\ Benchmark

cp Assets/Settings_\$3.ini Assets/pts_settings.ini
sed -i \"s/Resolution=/Resolution=\$1,\$2\\n#/g\" Assets/pts_settings.ini

HOME=\$DEBUG_REAL_HOME STEAM_COMPAT_CLIENT_INSTALL_PATH=\$DEBUG_REAL_HOME/.steam/steam STEAM_COMPAT_DATA_PATH=\$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/267130/ \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Proton\ -\ Experimental/proton run StarSwarm_D3D.exe -b -c pts_settings.ini -t 60 
sleep 1
killall -9 notepad.exe
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/267130/pfx/drive_c/users/steamuser/Documents/Star\ Swarm/Output_*.txt > \$LOG_FILE" > star-swarm
chmod +x star-swarm
