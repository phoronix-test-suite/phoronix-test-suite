#!/bin/sh

tar -zxvf pybench-2009-08-14.tar.gz

echo "#!/bin/sh
cd pybench-2009-08-14/
python pybench.py \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > pybench

chmod +x pybench
