#!/bin/sh

tar -xjf landscape_mountains.tar.bz2

echo "#!/bin/sh
cd Landscape\ Mountains/LandscapeMountains/Binaries/Linux
LIBFRAMETIME_FILE=\$LOG_FILE LD_PRELOAD=\$TEST_EXTENDS/libframetime/libframetime.so ./LandscapeMountains \$@ &
BENCH_PID=\`pidof LandscapeMountains\`
sleep 60
kill -9 \$BENCH_PID" > ue4-mountains

chmod +x ue4-mountains
