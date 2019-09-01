#!/bin/sh

tar -xjf atlantis_demo.tar.bz2

echo "#!/bin/sh
cd Atlantis/Atlantis/Binaries/Linux
LIBFRAMETIME_FILE=\$LOG_FILE LD_PRELOAD=\$TEST_EXTENDS/libframetime/libframetime.so ./Atlantis-Linux-Shipping \$@ &
BENCH_PID=\`pidof Atlantis-Linux-Shipping\`
sleep 120
kill -9 \$BENCH_PID" > ue4-atlantis

chmod +x ue4-atlantis
