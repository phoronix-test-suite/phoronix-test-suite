#!/bin/sh

tar -xzvf fftw-3.3.4.tar.gz
rm -rf fftw-mr
rm -rf fftw-stock

mv fftw-3.3.4 fftw-stock
cp -a fftw-stock fftw-mr

cd fftw-mr
./configure --enable-float --enable-sse
make

cd ~/fftw-stock
./configure
make
echo $? > ~/install-exit-status

cd ~/
echo "
#!/bin/sh

./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
" > fftw

chmod +x fftw

