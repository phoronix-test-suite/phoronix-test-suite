#!/bin/sh

echo "#!/bin/sh
[ ! -d \"/Volumes/V-Ray\ Benchmark\ 5.00.01/\" ] && hdid vray-benchmark-5.00.01.dmg
cd /Volumes/V-Ray\ Benchmark\ 5.00.01/V-Ray\ Benchmark.app/Contents/MacOS/
echo y | ./V-Ray\ Benchmark \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > v-ray
chmod +x v-ray
