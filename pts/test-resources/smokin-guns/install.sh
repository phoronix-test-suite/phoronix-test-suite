#!/bin/sh

unzip -o Smokin_Guns_1.0.zip
cd Smokin\'\ Guns/
chmod +x smokinguns.x86
cd ..


tar -zxvf smokinguns-benchmark-2.tar.gz
mkdir -p ~/.smokinguns/smokinguns/
mv -f q3config.cfg ~/.smokinguns/smokinguns/
mv -f demos/ ~/.smokinguns/smokinguns/

echo "#!/bin/sh
cd Smokin\'\ Guns/
rm -f libopenal.so.0
rm -f libGL.so
[ -r /usr/lib/libGL.so.1 -a ! -r /usr/lib/libGL.so ] && ln -fs /usr/lib/libGL.so.1 libGL.so
[ -r /usr/lib/libopenal.so.1 -a ! -r /usr/lib/libopenal.so.0 ] && ln -fs /usr/lib/libopenal.so.1 libopenal.so.0
LD_LIBRARY_PATH=. ./smokinguns.x86 \$@ > \$LOG_FILE 2>&1
cat \$LOG_FILE | grep fps" > smokin-guns
chmod +x smokin-guns
