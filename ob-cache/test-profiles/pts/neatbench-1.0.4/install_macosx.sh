#!/bin/sh

unzip -o NeatBench5_OSX64.zip

echo "#!/bin/sh
echo \"\n\" | ./NeatBench5 \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > neatbench
chmod +x neatbench
