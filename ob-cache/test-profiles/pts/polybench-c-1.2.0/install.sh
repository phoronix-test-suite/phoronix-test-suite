#!/bin/sh

tar -zxvf polybench-c-4.2.tar.gz


if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

cd polybench-c-4.2/
cc $CFLAGS -I utilities -I linear-algebra/kernels/3mm utilities/polybench.c linear-algebra/kernels/3mm/3mm.c -DPOLYBENCH_TIME -o 3mm_bench
cc $CFLAGS -I utilities -I datamining/correlation utilities/polybench.c datamining/correlation/correlation.c -DPOLYBENCH_TIME -o correlation_bench -lm
cc $CFLAGS -I utilities -I datamining/covariance utilities/polybench.c datamining/covariance/covariance.c -DPOLYBENCH_TIME -o covariance_bench
cc $CFLAGS -I utilities -I stencils/adi utilities/polybench.c stencils/adi/adi.c -DPOLYBENCH_TIME -o adi_bench
echo \$? > ~/test-exit-status

cd ~
echo "#!/bin/sh
cd polybench-c-4.2/
./\$@_bench > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > polybench-c
chmod +x polybench-c
