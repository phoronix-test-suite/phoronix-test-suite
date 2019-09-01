#!/bin/bash

tar -xjf xmlgen_rnd.tar.bz2

XMLLINT=`which xmllint`
echo $? > ~/install-exit-status

cat > system-libxml2 << EOT
#!/bin/sh
${XMLLINT} --repeat --timing --stream \$1 > /dev/null 2> \${LOG_FILE}
echo \$? > ~/test-exit-status
EOT

chmod +x system-libxml2
