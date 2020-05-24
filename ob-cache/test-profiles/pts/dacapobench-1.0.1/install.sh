#!/bin/sh

echo "#!/bin/sh
java -jar dacapo-9.12-MR1-bach.jar -t \$NUM_CPU_CORES --window 10 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > dacapobench
chmod +x dacapobench
