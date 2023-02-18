#!/bin/sh
HOME=\$DEBUG_REAL_HOME /cygdrive/c/Program\ Files\ \(x86\)/Steam/steam.exe steam://install/620
unzip -o portal2-demo-pts3.zip
mv pts3.dem "C:\Program Files (x86)\Steam\steamapps\common\Portal 2\portal2"
echo "#!/bin/sh
cd \"C:\Program Files (x86)\Steam\steamapps\common\Portal 2\"
rm -f portal2/console.log
HOME=\$DEBUG_REAL_HOME ./portal2.exe -game portal2 -condebug +cl_showfps 1 +timedemoquit pts3 -novid +mat_vsync 0 -mat_vsync 0 -fullscreen \$@
cat portal2/console.log > \$LOG_FILE" > portal2
chmod +x portal2