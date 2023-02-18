#!/bin/sh
if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi
HOME=$DEBUG_REAL_HOME steam steam://install/1659040
echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/*/pfx/drive_c/users/steamuser/hitman/profiledata.txt
HOME=\$DEBUG_REAL_HOME steam -applaunch 1659040 \$@
sleep 30
while pgrep -x \"hitman3.exe\" > /dev/null; do
    sleep 2
done
sleep 3
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/*/pfx/drive_c/users/steamuser/hitman/profiledata.txt > \$LOG_FILE" > hitman3
chmod +x hitman3