#!/bin/sh

steam steam://install/620

unzip -o portal2-demo-pts3.zip
mv pts3.dem "C:\Program Files (x86)\Steam\steamapps\common\Portal 2\portal2"

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Steam\steamapps\common\Portal 2\"
./hl2.exe -game portal2 +con_logfile 1.txt +cl_showfps 1 +timedemoquit pts3 -novid -mat_vsync 0 -fullscreen \$@
cat portal2/1.txt > \$LOG_FILE" > portal2
chmod +x portal2
