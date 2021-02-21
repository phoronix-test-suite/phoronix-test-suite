#!/bin/sh

unzip -o OctaneBench_2020_1_5_win.zip

echo "#!/bin/sh
cd OctaneBench_2020_1_5_win/
./octane-cli.exe  --benchmark -g 0 -a \$LOG_FILE" > octanebench
chmod +x octanebench
