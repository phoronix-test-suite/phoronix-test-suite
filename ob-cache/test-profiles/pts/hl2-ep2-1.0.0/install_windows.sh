#!/bin/sh

steam steam://install/420

unzip -o hl2-ep2-pts1-demo.zip
mv pts1.dem "C:\Program Files (x86)\Steam\steamapps\common\Half-Life 2\ep2"

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Steam\steamapps\common\Half-Life 2\"
./hl2.exe -game ep2 -steam +con_logfile 1.txt +cl_showfps 1 +timedemoquit pts1 -novid -mat_vsync 0 -fullscreen \$@
cat ep2/1.txt > \$LOG_FILE" > hl2-ep2
chmod +x hl2-ep2
