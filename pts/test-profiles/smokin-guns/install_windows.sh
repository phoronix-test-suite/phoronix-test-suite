#!/bin/sh

unzip -o Smokin_Guns_1.0.zip
mv SmokinGuns-1.1b4-update.zip "Smokin' Guns/"
cd "Smokin' Guns/"
unzip -o SmokinGuns-1.1b4-update.zip
cd ..

tar -zxvf smokinguns-benchmark-2.tar.gz
mv -f q3config.cfg "Smokin' Guns/smokinguns/"
mv -f demos/ "Smokin' Guns/smokinguns/"

echo "#!/bin/sh
cd \"Smokin' Guns/smokinguns/\"
smokinguns.exe +timedemo 1 +set demodone \"quit\" +set demoloop1 \"demo pts; set nextdemo vstr demodone\" +vstr demoloop1 \$@ > \$LOG_FILE" > smokin-guns
chmod +x smokin-guns
