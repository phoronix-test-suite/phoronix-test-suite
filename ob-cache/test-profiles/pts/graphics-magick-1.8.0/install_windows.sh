#!/bin/sh

./GraphicsMagick-1.3.30-Q16-win64-dll.exe /DIR=gm_ /SILENT

echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/gm benchmark -duration 60 convert \$TEST_EXTENDS/DSC_6782.png \$@ null: > \$LOG_FILE 2>&1" > graphics-magick
chmod +x graphics-magick
