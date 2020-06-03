#!/bin/sh


echo "#!/bin/sh

cd \"C:\Program Files\SPECopc\SPECViewperf\viewperf\viewperf10.0-x64\"

echo \"screenHeight  \$2
screenWidth  \$1\" > viewperf.config

./Run_\$3.bat
cat results/\$3-*/*result.txt > \$LOG_FILE" > specviewperf10-run
chmod +x specviewperf10-run

./SPECViewperf10.exe
