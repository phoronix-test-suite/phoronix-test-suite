#!/bin/sh

unzip -o scimark2_1c.zip -d scimark2_files
cd scimark2_files/
cc $CFLAGS -o scimark2 *.c -lm
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
cd scimark2_files/
./scimark2 -large > \$LOG_FILE 2>&1" > scimark2
chmod +x scimark2
