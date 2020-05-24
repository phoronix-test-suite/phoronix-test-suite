#!/bin/sh

tar -xf aom-200.tar.xz
cd aom/build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z

# Current AOMedia Git has MAX_NUM_THREADS value of 64, don't go over 64 threads or error

echo "#!/bin/sh

if [ \"\$NUM_CPU_CORES\" -gt 64 ]; then
	NUM_CPU_CORES=64
fi

./aom/build/aomenc --threads=\$NUM_CPU_CORES \$@ -o test.av1 Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m > 1.log 2>&1
echo \$? > ~/test-exit-status
sed \$'s/[^[:print:]\t]/\\n/g' 1.log > \$LOG_FILE
rm -f test.av1" > aom-av1
chmod +x aom-av1
