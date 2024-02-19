#!/bin/sh
rm -rf easywave
rm -rf easywave_r34_src
tar -xf easywave_r34_src.tar.gz
tar -xf easywave_examples_1.tar.gz
mv easywave easywave_r34_src
cd easywave_r34_src
CXXFLAGS="-O3 $CXXFLAGS" ./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./easywave_r34_src/src/easywave \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -f eWave*
rm -f examples/*.png" > easywave
chmod +x easywave
