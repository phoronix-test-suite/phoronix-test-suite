#!/bin/sh

unzip -o NeatBench5_Win64.zip
chmod +x NeatBench5.exe

echo "#!/bin/sh
echo \"\n\" | ./NeatBench5.exe \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > neatbench
chmod +x neatbench
