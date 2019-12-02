#!/bin/sh

unzip -o oidn-1.0.0.x64.vc14.windows.zip
cp memorial.pfm oidn-1.0.0.x64.vc14.windows/bin/

echo "#!/bin/sh
cd oidn-1.0.0.x64.vc14.windows/bin/
./denoise.exe \$@ -threads \$NUM_CPU_CORES  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oidn
chmod +x oidn
