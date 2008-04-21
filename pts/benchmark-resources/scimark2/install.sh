#!/bin/sh

cd $1

if [ ! -f scimark2_1c.zip ]
 
	then
     wget http://math.nist.gov/scimark2/scimark2_1c.zip -O scimark2_1c.zip
fi

unzip -o scimark2_1c.zip -d scimark2_files
cd scimark2_files/
g++ -o scimark2 -O *.c
cd ..

echo "#!/bin/sh
cd scimark2_files/

./scimark2 -large" > scimark2
chmod +x scimark2
