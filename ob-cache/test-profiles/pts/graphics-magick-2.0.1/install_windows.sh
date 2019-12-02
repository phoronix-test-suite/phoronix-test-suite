#!/bin/sh

./GraphicsMagick-1.3.33-Q16-win64-dll.exe /DIR=gm_ /SILENT
unzip -o sample-photo-6000x4000-1.zip

OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/gm convert sample-photo-6000x4000.JPG input.mpc

echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/gm benchmark -duration 60 convert input.mpc \$@ null: > \$LOG_FILE 2>&1" > graphics-magick
chmod +x graphics-magick
