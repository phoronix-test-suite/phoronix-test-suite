#!/bin/bash

tar -xzf misc.tar.gz

T2RGB=`which tiff2rgba`
echo $? > ~/install-exit-status

cat > system-decompress-tiff << EOT
#!/bin/sh

ST=\`date +%s.%N\`
${T2RGB} -c none misc/4.2.03.tiff mandril.rgba
ET=\`date +%s.%N\`

echo "(\$ET - \$ST) * 1000" | bc > \$LOG_FILE
echo \$? > ~/test-exit-status
EOT

chmod +x system-decompress-tiff
