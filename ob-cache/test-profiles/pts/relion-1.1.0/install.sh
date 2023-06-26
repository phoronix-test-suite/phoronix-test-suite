#!/bin/bash
tar -xf relion-4.0.1.tar.gz
cd relion-4.0.1
mkdir build
cd build
cmake .. -DCMAKE_BUILD_TYPE=Release -DGUI=OFF
make -j $NUM_CPU_CORES
retVal=$?
if [ $retVal -ne 0 ]; then
    echo $retVal > ~/install-exit-status
    exit $retVal
fi
cd ~/
tar -xf relion_benchmark.tar.gz
cat>relion<<EOT
#!/bin/sh
cd relion_benchmark/
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ~/relion-4.0.1/build/bin/relion_refine_mpi --i ~/relion_benchmark/Particles/shiny_2sets.star --o out \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x relion

