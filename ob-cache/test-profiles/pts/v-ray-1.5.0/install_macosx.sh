#!/bin/sh
echo "#!/bin/sh
[ ! -d \"/Volumes/V-Ray\ Benchmark\ 6.00.00/\" ] && hdid vray-benchmark-6.00.00.dmg
cd /Volumes/V-Ray\ Benchmark\ 6.00.00/V-Ray\ Benchmark.app/Contents/MacOS/
echo y | ./V-Ray\ Benchmark \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > v-ray
chmod +x v-ray
