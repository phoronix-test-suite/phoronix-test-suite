#!/bin/sh

#HOME=$DEBUG_REAL_HOME steam steam://install/570

tar -xjvf dota2-pts-1971360796.dem.tar.bz2
mv dota2-pts-1971360796.dem $DEBUG_REAL_HOME/Library/Application\ Support/Steam/steamapps/common/dota\ 2\ beta/game/dota

echo "#!/bin/sh
HOME=\$DEBUG_REAL_HOME
cd \$DEBUG_REAL_HOME/./Library/Application\ Support/Steam/steamapps/common/dota\ 2\ beta/game
mv -f \$HOME//Library/Application\ Support/Steam/steamapps/common/dota\ 2\ beta/game/dota/Source2Bench.csv \$HOME//Library/Application\ Support/Steam/steamapps/common/dota\ 2\ beta/game/dota/Source2Bench.csv.1
# -testscript_inline \\\"Test_WaitForCheckPoint DemoPlaybackFinished\; quit\\\"
./dota.sh +con_logfile \$LOG_FILE +timedemoquit dota2-pts-1971360796 +demo_quitafterplayback 1 +cl_showfps 2 +fps_max 0 -nosound -noassert -console -fullscreen +timedemo_start 50000 +timedemo_end 51000 -autoconfig_level 3 -testscript_inline \\\"Test_WaitForCheckPoint DemoPlaybackFinished\; quit\\\" \$@
cat \$HOME//Library/Application\ Support/Steam/steamapps/common/dota\ 2\ beta/game/dota/Source2Bench.csv >> \$LOG_FILE" > dota2-benchmark
chmod +x dota2-benchmark
