#!/bin/sh

tar -zxf NAMD_3.0.1_Linux-x86_64-multicore-CUDA.tar.gz
unzip -o f1atpase.zip
unzip -o stmv.zip
sed -i 's/\/usr\/tmp/\/tmp/g'                               f1atpase/f1atpase.namd
sed -i 's/1-4scaling/oneFourScaling/g'                      f1atpase/f1atpase.namd
sed -i 's/timestep                1.0/timestep 2/g'         f1atpase/f1atpase.namd
sed -i 's/fullElectFrequency      4/fullElectFrequency 2/g' f1atpase/f1atpase.namd
sed -i 's/outputEnergies          1/outputEnergies 1000/g'  f1atpase/f1atpase.namd
sed -i 's/outputTiming		20/outputTiming 1000/g'         f1atpase/f1atpase.namd
sed -i 's/numsteps                500/numsteps 1000/g'      f1atpase/f1atpase.namd
sed -i '12i GPUresident on'                                 f1atpase/f1atpase.namd
sed -i 's/fixedAtoms  on/fixedAtoms off/g'                  f1atpase/f1atpase.namd

sed -i 's/\/usr\/tmp/\/tmp/g'                           stmv/stmv.namd
sed -i 's/1-4scaling/oneFourScaling/g'                  stmv/stmv.namd
sed -i 's/timestep            1.0/timestep 2/g'         stmv/stmv.namd
sed -i 's/fullElectFrequency  4/fullElectFrequency 2/g' stmv/stmv.namd
sed -i 's/outputEnergies      20/outputEnergies 1000/g' stmv/stmv.namd
sed -i 's/outputTiming        20/outputTiming 1000/g'   stmv/stmv.namd
sed -i 's/numsteps            500/numsteps 1000/g'      stmv/stmv.namd
sed -i '11i GPUresident on'                             stmv/stmv.namd

cd ~
echo "#!/bin/bash
cd NAMD_3.0.1_Linux-x86_64-multicore-CUDA
./namd3 +idlepoll +p1 \$@ > \$LOG_FILE 2>&1" > namd-cuda
chmod +x namd-cuda
