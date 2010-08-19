#!/bin/sh

cat > compress-gzip <<EOT
#!/bin/sh
cat compressfile | gzip -c > /dev/null 2>&1
EOT
chmod +x compress-gzip 


