#!/bin/sh
chmod +x FluidX3D-Benchmark-*.exe
echo "#!/bin/sh
./FluidX3D-Benchmark-\$1-Windows-23.exe > \$LOG_FILE 2>&1" > fluidx3d
chmod +x fluidx3d
