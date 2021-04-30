
tar -xf WRF-4.2.2.tar.gz
cd WRF-4.2.2

export NETCDF=/usr
export NETCDF_classic=1
echo 34 | ./configure

sed -i 's/io_netcdf -lwrfio_nf -L\/usr\/lib/io_netcdf -lwrfio_nf -L\/usr\/lib -lnetcdff -lnetcdf/' configure.wrf 

./compile -j $NUM_CPU_CORES em_real 2>&1
echo $? > ~/install-exit-status

cd ~

tar -xf conus2.5km.tar.gz
mv conus2.5km/* WRF-4.2.2/run

cat>wrf<<EOT
#!/bin/sh
cd WRF-4.2.2/run
export NETCDF=/usr
export NETCDF_classic=1
export OMP_NUM_THREADS=1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./wrf.exe > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x wrf

