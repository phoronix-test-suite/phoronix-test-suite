#!/bin/sh

tar -xjf elemental_demo.tar.bz2

echo "#!/bin/sh
cd Elemental\ Demo/ElementalDemo/Binaries/Linux/
LIBFRAMETIME_FILE=\$LOG_FILE LD_PRELOAD=\$TEST_EXTENDS/libframetime/libframetime.so ./ElementalDemo \$@ &
BENCH_PID=\`pidof ElementalDemo\`
sleep 60
kill -9 \$BENCH_PID" > ue4-elemental

chmod +x ue4-elemental
