#!/bin/sh

unzip -o OctaneBench_4_00b_win.zip

echo "#!/bin/sh
cd OctaneBench_4_00b_win/
./octane-cli.exe  --benchmark --no-gui -g 0 -a \$LOG_FILE" > octanebench
chmod +x octanebench
