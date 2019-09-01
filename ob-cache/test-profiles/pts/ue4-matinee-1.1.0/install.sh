#!/bin/sh

tar -xjf matinee_demo.tar.bz2

echo "#!/bin/sh
cd Matinee\ Fight\ Scene\ Demo/MatineeFightScene/Binaries/Linux
LIBFRAMETIME_FILE=\$LOG_FILE LD_PRELOAD=\$TEST_EXTENDS/libframetime/libframetime.so ./MatineeFightScene \$@ &
BENCH_PID=\`pidof MatineeFightScene\`
sleep 60
kill -9 \$BENCH_PID" > ue4-matinee

chmod +x ue4-matinee
