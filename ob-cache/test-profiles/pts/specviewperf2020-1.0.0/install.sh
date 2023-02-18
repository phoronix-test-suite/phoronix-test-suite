#!/bin/bash

if [ ! -d /opt/SPEC/SPECviewperf2020 ]; then
  dpkg -i viewperf2020_3.0_amd64.deb
fi

if [ ! -d /opt/SPEC/SPECviewperf2020 ]; then
  echo "ERROR: SPECViewPerf 2020 v3.0 must be installed on the system!"
  echo "/opt/SPEC/SPECviewperf2020 is not found"
  echo "If viewperf2020_3.0_amd64.deb is found on the system, PTS will try to install it"
  echo "Otherwise first acquire SPECViewPerf from https://gwpg.spec.org/bench-marks/specviewperf-2020v3-0-linux-edition/"
  echo 2 > ~/install-exit-status
  exit
fi

echo "#!/bin/bash

rm -f ~/Documents/SPECresults/SPECviewperf2020/results_*/resultCSV.csv
cd /opt/SPEC/SPECviewperf2020
./RunViewperf -nogui \$@
cat ~/Documents/SPECresults/SPECviewperf2020/results_*/resultCSV.csv > \$LOG_FILE" > specviewperf2020
chmod +x specviewperf2020
