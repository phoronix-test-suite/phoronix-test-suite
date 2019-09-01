#!/bin/sh
tar -xf rarosx-5.6.1.tar.gz

echo "#!/bin/sh
./rar/rar a r to-compress.rar to-compress/*" > compress-rar
chmod +x compress-rar 


