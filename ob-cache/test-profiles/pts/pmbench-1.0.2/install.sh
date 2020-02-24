#!/bin/sh

tar -xf jisooy-pmbench-46a3d394ca7b.tar.xz
cd jisooy-pmbench-46a3d394ca7b
make pmbench
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/bash
cd jisooy-pmbench-46a3d394ca7b
./pmbench \$@ 60 > \$LOG_FILE 2>&1" > pmbench
chmod +x pmbench
