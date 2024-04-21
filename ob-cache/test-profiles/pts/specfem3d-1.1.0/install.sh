#!/bin/sh
tar -xf specfem3d-4.1.1.tar.gz
cd specfem3d-4.1.1
if [ -d "/usr/include/openmpi-x86_64/" ]; then
  export MPI_INC="/usr/include/openmpi-x86_64/"
fi
./configure --enable-openmp
make all -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~/
cat>specfem3d<<EOT
#!/bin/sh
cd specfem3d-4.1.1/EXAMPLES/applications
cd \$1
rm -f OUTPUT_FILES/output_solver.txt
sed -i '/^NPROC/d' DATA/Par_file
echo "NPROC                           = \$NUM_CPU_PHYSICAL_CORES" >> DATA/Par_file
OMP_NUM_THREADS=\$CPU_THREADS_PER_CORE ./run_this_example.sh > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
cat OUTPUT_FILES/output_solver.txt >> \$LOG_FILE
EOT
chmod +x specfem3d
