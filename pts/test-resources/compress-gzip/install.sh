#!/bin/sh

cat > compress-gzip <<EOT
#!/bin/sh
\$TIMER_START
cat compressfile | gzip -c > /dev/null 2>&1
\$TIMER_STOP
EOT
chmod +x compress-gzip 


