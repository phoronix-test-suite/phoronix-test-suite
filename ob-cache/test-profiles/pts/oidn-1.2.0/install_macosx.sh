#!/bin/sh

tar -xf oidn-1.2.0.x86_64.macos.tar.gz
cp memorial.pfm oidn-1.2.0.x86_64.macos/bin/

echo "#!/bin/sh
cd oidn-1.2.0.x86_64.macos/bin/
./denoise \$@ -threads \$NUM_CPU_CORES  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oidn
chmod +x oidn
