#!/bin/bash

tar -xvf misc.tar.gz
convert misc/4.2.03.tiff mandrill.jpeg

DJPG=`which djpeg`
echo $? > ~/install-exit-status

cat > system-libjpeg << EOT
#!/bin/sh

case \"\$1\" in
\"int\")
  DCT=\"-dct int\"
  ;;
\"fast\")
  DCT=\"-dct fast\"
  ;;
\"float\")
  DCT=\"-dct float\"
  ;;
esac

ST=\`date +%s.%N\`
${DJPG} ${DCT} mandrill.jpeg > /dev/null 2>&1
ET=\`date +%s.%N\`

echo "(\$ET - \$ST) * 1000" | bc > \$LOG_FILE
EOT

chmod +x system-libjpeg
