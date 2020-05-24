#!/bin/sh

chmod +x vray-benchmark-4.10.07

echo "#!/bin/sh
[ ! -d \"/Volumes/V-Ray\ Benchmark\ 4.10.07/\" ] && hdid vray-benchmark-4.10.07.dmg
cd /Volumes/V-Ray\ Benchmark\ 4.10.07/V-Ray\ Benchmark.app/Contents/MacOS/
./V-Ray\ Benchmark \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > v-ray
chmod +x v-ray
