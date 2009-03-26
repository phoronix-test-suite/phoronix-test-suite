#!/bin/sh

tar -xvf c-ray-1.1.tar.gz

cd c-ray-1.1/
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
cd c-ray-1.1/
RT_THREADS=\$((\$NUM_CPU_CORES * 16))
./c-ray-mt -t \$RT_THREADS -s 1600x1200 -r 8 -i sphfract -o output.ppm > \$LOG_FILE 2>&1" > c-ray
chmod +x c-ray
