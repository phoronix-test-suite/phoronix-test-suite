#!/bin/sh
tar -xf rarosx-6.0.2.tar.gz

echo "#!/bin/sh
./rar/rar a r to-compress.rar to-compress/*" > compress-rar
chmod +x compress-rar 


