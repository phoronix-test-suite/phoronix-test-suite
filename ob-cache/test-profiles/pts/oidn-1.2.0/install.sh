#!/bin/sh

tar -xf oidn-1.2.0.x86_64.linux.tar.gz
cp memorial.pfm oidn-1.2.0.x86_64.linux/bin/

echo "#!/bin/sh
cd oidn-1.2.0.x86_64.linux/bin/
./denoise \$@ -threads \$NUM_CPU_CORES  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oidn
chmod +x oidn
