#!/bin/bash

GZIP=`which gzip`
echo $? > ~/install-exit-status

cat > system-decompress-gzip << EOT
#!/bin/sh
${GZIP} -d --stdout qt-everywhere-opensource-src-5.0.0.tar.gz > /dev/null 2>&1
echo -e "\n" >> \${LOG_FILE}
EOT
chmod +x system-decompress-gzip

