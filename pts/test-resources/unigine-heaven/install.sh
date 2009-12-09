#!/bin/sh

chmod +x Unigine_Heaven.run
./Unigine_Heaven.run

tar -jxf unigine-heaven-cfg-1.tar.bz2
mv -f *.cfg heaven/data/

echo "#!/bin/sh
cd heaven/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/Heaven \$@ > \$LOG_FILE 2>&1" > unigine-heaven
chmod +x unigine-heaven

