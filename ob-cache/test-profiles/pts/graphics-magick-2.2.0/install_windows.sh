#!/bin/sh
7z x GraphicsMagick-1.3.43-windows.7z -aoa
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/gm convert sample-photo-mars.jpg input.mpc
echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/gm benchmark -duration 60 convert input.mpc \$@ null: > \$LOG_FILE 2>&1" > graphics-magick
chmod +x graphics-magick
