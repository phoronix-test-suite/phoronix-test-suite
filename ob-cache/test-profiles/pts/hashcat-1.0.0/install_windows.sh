#!/bin/sh

7z x hashcat-6.1.1.7z -aoa

echo "#!/bin/sh
cd hashcat-6.1.1
./hashcat.exe -b \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > hashcat
chmod +x hashcat
