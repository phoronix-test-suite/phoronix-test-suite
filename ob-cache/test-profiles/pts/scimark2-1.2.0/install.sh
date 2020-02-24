#!/bin/sh

unzip -o scimark2_1c.zip -d scimark2_files
cd scimark2_files/
c++ $CXXFLAGS -o scimark2 -O *.c
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
cd scimark2_files/
./scimark2 -large > \$LOG_FILE 2>&1" > scimark2
chmod +x scimark2
