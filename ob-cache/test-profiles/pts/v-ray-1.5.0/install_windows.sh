#!/bin/sh
chmod +x vray-benchmark-6.00.00-cli.exe
echo "#!/bin/sh
echo y | ./vray-benchmark-6.00.00-cli.exe \$@ > \$LOG_FILE" > v-ray
chmod +x v-ray

