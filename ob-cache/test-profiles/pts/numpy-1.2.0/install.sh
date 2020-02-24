#!/bin/sh

tar -xvf numpy-benchmarks-20190903.tar.gz
echo $? > ~/install-exit-status

echo "import sys
product = 1
count = 0
with open(sys.argv[-1]) as fp:
    for l in fp.readlines():
        parts = l.split()
        avg = float(parts[3])
        product *= avg
        count += 1
gmean = product**(1.0/count)
score = 1000000.0/gmean
print(\"Geometric mean score: %.2f\" % score)" > result_parser.py


echo "#!/bin/sh
cd numpy-benchmarks-master/
python3 run.py -t python -p python3 > numpy_log
echo \$? > ~/test-exit-status
cat numpy_log > \$LOG_FILE
python3 ../result_parser.py numpy_log >> \$LOG_FILE" > numpy
chmod +x numpy
