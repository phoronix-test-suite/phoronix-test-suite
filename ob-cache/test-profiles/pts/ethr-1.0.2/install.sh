#!/bin/sh
rm -rf ethr-master
rm -rf go
rm -rf .cache
unzip -o ethr-20190102.zip
cd ethr-master
go get -t -d -v
go build
mv ethr-master ethr
cd ~

echo "#!/bin/sh
cd ethr-master
./ethr \$@ > \$LOG_FILE 2>1" > ethr
chmod +x ethr
