#!/bin/sh

tar -zxvf pybench-2018-02-16.tar.gz

echo "#!/bin/sh
cd pybench-2018-02-16/
python3 pybench.py \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > pybench

chmod +x pybench
