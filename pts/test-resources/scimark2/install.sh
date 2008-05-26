#!/bin/sh

unzip -o scimark2_1c.zip -d scimark2_files
cd scimark2_files/
g++ -o scimark2 -O *.c
cd ..

echo "#!/bin/sh
cd scimark2_files/

./scimark2 -large" > scimark2
chmod +x scimark2
