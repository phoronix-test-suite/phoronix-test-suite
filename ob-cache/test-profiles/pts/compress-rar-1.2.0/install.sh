#!/bin/sh
tar -xf rarlinux-x64-6.0.2.tar.gz

echo "#!/bin/sh
./rar/rar a r to-compress.rar to-compress/*" > compress-rar
chmod +x compress-rar 


