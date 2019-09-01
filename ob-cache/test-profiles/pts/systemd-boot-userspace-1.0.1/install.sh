#!/bin/sh

SA=`which systemd-analyze`
echo $? > ~/install-exit-status

cat > systemd-boot-userspace << EOT
#!/bin/sh

${SA} | \
  sed -e 's/in /\\n/' -e 's/[+|=] /\\n/g' -e 's/ms//g' | \
  awk '/userspace/ {print \$1}' > \$LOG_FILE 2>&1
EOT

chmod +x systemd-boot-userspace
