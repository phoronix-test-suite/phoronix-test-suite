#!/bin/sh

tar -xjvf FinanceBench-20160606.tar.bz2

cd ~/FinanceBench-20160606/Black-Scholes/OpenCL
c++ -O3 -o  blackScholesAnalyticEngine.exe blackScholesAnalyticEngine.c -lOpenCL

cd ~/FinanceBench-20160606/Monte-Carlo/OpenCL
c++ -O3 -o monteCarloEngine.exe monteCarloEngine.c -lOpenCL

echo $? > ~/install-exit-status

cd ~


cd ~/
echo "#!/bin/bash
cd ~/FinanceBench-20160606/
cd \$(dirname \"\$@\")
echo '' | ./\$(basename \"\$@\") > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > financebench
chmod +x financebench
