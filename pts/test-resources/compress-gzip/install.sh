#!/bin/sh

cat > gzip_process <<EOT
#!/bin/sh
cat compressfile | gzip -c > /dev/null
EOT
chmod +x gzip_process

cat > compress-gzip <<EOT
#!/bin/sh
/usr/bin/time -f "Gzip Compress Time: %e Seconds" ./gzip_process 2>&1
EOT
chmod +x compress-gzip 


