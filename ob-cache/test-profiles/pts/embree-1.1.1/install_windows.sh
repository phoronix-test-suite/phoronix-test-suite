#!/bin/sh

unzip -o embree-3.9.0.x64.vc14.windows.zip
unzip -o asian_dragon.zip
unzip -o asian_dragon_obj.zip
unzip -o crown.zip

echo "#!/bin/bash
FORMATTED=\"\${\$@/pathtracer /pathtracer.exe }\"
FORMATTED=\"\${\$@/pathtracer_ispc /pathtracer_ispc.exe }\"
./embree-3.9.0.x64.vc14.windows/bin/\$@ --threads \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > embree
chmod +x embree
