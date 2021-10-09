#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/550
echo $? > ~/install-exit-status

unzip -o l4d2-pts1.zip
mv pts1.dem $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Left\ 4\ Dead\ 2/left4dead2

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Left\ 4\ Dead\ 2/
rm -f left4dead2/console.log
./hl2_linux -game left4dead2 -condebug -conclearlog -mat_vsync 0 +cl_showfps 1 +timedemoquit pts1 -novid  -fullscreen \$@ > \$LOG_FILE 2>&1
cat  left4dead2/console.log > \$LOG_FILE" > l4d2

chmod +x l4d2
