#!/bin/sh

unzip -o aom-200-windows.zip
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z
chmod +x aomenc.exe

echo "#!/bin/sh

if [ \"\$NUM_CPU_CORES\" -gt 64 ]; then
	NUM_CPU_CORES=64
fi

./aomenc.exe --threads=\$NUM_CPU_CORES \$@ -o test.av1 Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m > 1.log 2>&1
echo \$? > ~/test-exit-status
sed \$'s/[^[:print:]\t]/\\n/g' 1.log > \$LOG_FILE
rm -f test.av1" > aom-av1
chmod +x aom-av1
