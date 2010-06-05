#!/bin/sh

tar xvf stresscpu2.tgz

cat> launch <<'EOT'
#!/bin/sh
case $OS_ARCH in
    x86_64)
    stresscpu2/stresscpu2_linux64 -t $@ | grep FAIL > $LOG_FILE
    ;;
    i?86 | i86*)
    stresscpu2/stresscpu2_linux32 -t $@ | grep FAIL > $LOG_FILE
    ;;
esac
EOT
chmod +x launch
