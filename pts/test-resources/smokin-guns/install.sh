#!/bin/sh

unzip -o Smokin_Guns_1.0.zip
cd Smokin\'\ Guns/
chmod +x smokinguns.x86
cd ..


tar -xvf smokinguns-benchmark-1.tar.gz
mkdir -p ~/.smokinguns/smokinguns/
mv -f q3config.cfg ~/.smokinguns/smokinguns/
mv -f demos/ ~/.smokinguns/smokinguns/

echo "#!/bin/sh
cd Smokin\'\ Guns/

./smokinguns.x86 \$@ > \$LOG_FILE 2>&1
cat \$LOG_FILE | grep fps" > smokin-guns
chmod +x smokin-guns
