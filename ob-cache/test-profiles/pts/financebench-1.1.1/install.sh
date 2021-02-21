#!/bin/sh

tar -xf FinanceBench-20160725.tar.xz

cd ~/FinanceBench-20160725/Black-Scholes/OpenCL
c++ -O3 -o  blackScholesAnalyticEngine.exe blackScholesAnalyticEngine.c -lOpenCL

cd ~/FinanceBench-20160725/Monte-Carlo/OpenCL
c++ -O3 -o monteCarloEngine.exe monteCarloEngine.c -lOpenCL

cd ~/FinanceBench-20160725/Bonds/OpenMP/
make

cd ~/FinanceBench-20160725/Monte-Carlo/OpenMP/
make

cd ~/FinanceBench-20160725/Repo/OpenMP/
make
echo $? > ~/install-exit-status

cd ~


cd ~/
echo "#!/bin/bash
cd ~/FinanceBench-20160725/
cd \$(dirname \"\$@\")
echo '' | ./\$(basename \"\$@\") > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > financebench
chmod +x financebench
