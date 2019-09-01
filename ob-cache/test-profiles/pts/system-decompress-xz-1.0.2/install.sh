#!/bin/bash

XZ=`which xz`
echo $? > ~/install-exit-status

cat > system-decompress-xz << EOT
#!/bin/sh
${XZ} -dk --stdout linux-3.7.tar.xz > /dev/null 2>&1
EOT

chmod +x system-decompress-xz
