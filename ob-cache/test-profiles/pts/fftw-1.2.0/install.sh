#!/bin/sh

tar -xzvf fftw-3.3.6-pl2.tar.gz
rm -rf fftw-mr
rm -rf fftw-stock

mv fftw-3.3.6-pl2 fftw-stock
cp -a fftw-stock fftw-mr

AVX_TUNING=""
if [ $OS_TYPE = "Linux" ]
then
    if grep avx512 /proc/cpuinfo > /dev/null
    then
	AVX_TUNING="$AVX_TUNING --enable-avx512"
    fi
    if grep avx2 /proc/cpuinfo > /dev/null
    then
	AVX_TUNING="$AVX_TUNING --enable-avx2"
    fi
fi

cd fftw-mr
./configure --enable-float --enable-sse --enable-threads --enable-openmp $AVX_TUNING
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status

cd ~/fftw-stock
./configure --enable-threads --enable-openmp
make -j $NUM_CPU_JOBS

cd ~/
echo "
#!/bin/sh

./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
" > fftw

chmod +x fftw

