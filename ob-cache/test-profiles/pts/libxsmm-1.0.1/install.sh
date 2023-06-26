#!/bin/bash
unzip -o libxsmm-29504031f7234b412e6a5079d826ab1375f11408.zip
cd libxsmm-29504031f7234b412e6a5079d826ab1375f11408
sed -i 's/libxsmm_timer_tickint start/libxsmm_timer_tickint start;/g' samples/eigen/eigen_tensor.cpp # bug fix
make JIT=1 -j $NUM_CPU_CORES
make JIT=1 samples -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
cat>libxsmm<<EOT
#!/bin/sh
cd libxsmm-29504031f7234b412e6a5079d826ab1375f11408/samples/utilities/smmbench
./specialized 0 \$@ 0 500 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x libxsmm

