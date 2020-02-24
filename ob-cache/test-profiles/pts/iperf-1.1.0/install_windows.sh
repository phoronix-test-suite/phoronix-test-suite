#!/bin/sh

unzip -o iperf-3.1.3-win64.zip

cd ~
echo "#!/bin/sh
cd iperf-3.1.3-win64
cmd /c iperf3.exe \$@ > \$LOG_FILE" > iperf
chmod +x iperf
