#!/bin/sh
unzip -o embree-4.3.0.sycl.x64.windows.zip
unzip -o asian_dragon.zip
unzip -o asian_dragon_obj.zip
unzip -o crown.zip
echo "#!/bin/bash
FORMATTED=\"\${@/pathtracer /pathtracer.exe }\"
FORMATTED=\"\${@/pathtracer_ispc /pathtracer_ispc.exe }\"
FORMATTED=\"\${@/pathtracer_sycl /pathtracer_sycl.exe }\"
./bin/embree_\$FORMATTED --threads \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > embree
chmod +x embree
