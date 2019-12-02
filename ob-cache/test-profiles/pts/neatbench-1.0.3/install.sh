#!/bin/sh

tar -xvf NeatBench5_Linux64.tgz

echo "#!/bin/sh
echo \"\n\" | ./NeatBench5 \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > neatbench
chmod +x neatbench
