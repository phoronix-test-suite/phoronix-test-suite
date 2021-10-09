#!/bin/sh

7z x hashcat-6.2.4.7z -aoa

echo "#!/bin/sh
cd hashcat-6.2.4
./hashcat.exe -b \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > hashcat
chmod +x hashcat
