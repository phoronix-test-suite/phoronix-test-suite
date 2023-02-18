#!/bin/sh
HOME=\$DEBUG_REAL_HOME  /cygdrive/c/Program\ Files\ \(x86\)/Steam/steam.exe steam://install/1659040
USERNAME=`basename $DEBUG_REAL_HOME`
echo "#!/bin/bash
rm -f /cygdrive/c/Users/$USERNAME/hitman/profiledata.txt
HOME=\$DEBUG_REAL_HOME  /cygdrive/c/Program\ Files\ \(x86\)/Steam/steam.exe -applaunch 1659040 \$@
sleep 30
until [ -e /cygdrive/c/Users/$USERNAME/hitman/profiledata.txt ]
do
     sleep 5
done
cat /cygdrive/c/Users/$USERNAME/hitman/profiledata.txt > \$LOG_FILE" > hitman3
chmod +x hitman3