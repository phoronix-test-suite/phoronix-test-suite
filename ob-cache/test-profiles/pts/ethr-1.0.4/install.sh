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

# Start server in case doing localhost test
./ethr -s &
ETHR_SERVER_PID=\$!
sleep 3

./ethr \$@ > \$LOG_FILE 2>1

kill \$ETHR_SERVER_PID
sleep 3" > ethr
chmod +x ethr
