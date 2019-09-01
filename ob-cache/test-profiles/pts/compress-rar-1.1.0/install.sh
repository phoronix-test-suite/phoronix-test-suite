#!/bin/sh
tar -xf rarlinux-x64-5.6.1.tar.gz

echo "#!/bin/sh
./rar/rar a r to-compress.rar to-compress/*" > compress-rar
chmod +x compress-rar 


