#!/bin/sh
echo "#!/bin/sh
cd godot-build
scons -j \$NUM_CPU_CORES platform=x11 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > build-godot
chmod +x build-godot
