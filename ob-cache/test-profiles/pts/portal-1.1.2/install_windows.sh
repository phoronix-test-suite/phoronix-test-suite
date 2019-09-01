#!/bin/sh

steam steam://install/400

unzip -o pts-portal-1.zip
mv pts-portal-1.dem "C:\Program Files (x86)\Steam\steamapps\common\Portal\portal"

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Steam\steamapps\common\Portal\"
./hl2.exe -game portal +con_logfile 1.txt +cl_showfps 1 +timedemoquit pts-portal-1 -novid -fullscreen \$@
cat portal/1.txt > \$LOG_FILE" > portal
chmod +x portal
