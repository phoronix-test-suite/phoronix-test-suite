#!/bin/sh

chmod +x vray-benchmark-5.02.00-cli.exe
echo "#!/bin/sh
echo y | ./vray-benchmark-5.02.00-cli.exe \$@ > \$LOG_FILE" > v-ray
chmod +x v-ray

