#!/bin/sh

tar -jxf supertuxkart-0.6-linuxi486.tar.bz2

mkdir .supertuxkart/
tar -zxvf supertuxkart-1.tar.gz
mv config .supertuxkart/

echo "#!/bin/sh
cd supertuxkart-0.6-linuxi486/
LD_LIBRARY_PATH=./bin/: bin/supertuxkart \$@ > \$LOG_FILE 2>&1
cat \$LOG_FILE | grep fps" > supertuxkart
chmod +x supertuxkart

