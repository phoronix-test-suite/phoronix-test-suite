#!/bin/sh

SA=`which systemd-analyze`
echo $? > ~/install-exit-status

cat > systemd-boot-kernel << EOT
#!/bin/sh

#get kernel time
KTIME=\`${SA} | \
  sed -e 's/in /\\n/' -e 's/[+|=] /\\n/g' -e 's/ms//g' | \
  awk '/kernel/ {print \$1}'\`
                                                                                
# get initramfs time                                                            
ITIME=\`${SA} | \
  sed -e 's/in /\n/' -e 's/[+|=] /\n/g' -e 's/ms//g' | \
  awk '/initramfs/ {print \$1}'\`
                                                                                
echo \$((KTIME + ITIME)) > \$LOG_FILE 2>&1
EOT

chmod +x systemd-boot-kernel
