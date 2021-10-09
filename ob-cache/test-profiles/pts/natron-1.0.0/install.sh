#!/bin/s
h
tar -xf Natron-2.4.0-Linux-64-no-installer.tar.xz
unzip -o Natron_2.3.12_Spaceship.zip
echo "#!/bin/bash
cd Natron-2.4.0-Linux-64-no-installer
./Natron -b ~/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > natron
chmod +x natron
