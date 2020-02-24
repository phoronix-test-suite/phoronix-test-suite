#!/bin/sh

unzip -o Apache24-2.4.29-x64-vc14-r2-ah.zip
unzip -o nginx-1.9.9.zip

tar -xf apache-ab-test-files-1.tar.gz
mv -f test.html nginx-1.9.9/html
mv -f pts.png nginx-1.9.9/html

sed -i 's/        listen       80;/        listen       8089;/g' nginx-1.9.9/conf/nginx.conf
echo "#!/bin/sh
cd ~/nginx-1.9.9
./nginx.exe &
sleep 10

cd ~/Apache24/bin
AB_ARGS=\`echo \"\$@\" | sed \"s/localhost/127.0.0.1/g\"\`
echo \$AB_ARGS
./ab.exe -r -n \$AB_ARGS > \$LOG_FILE

cd ~/nginx-1.9.9
./nginx.exe -s quit
rm -f nginx_/logs/*
sleep 5" > nginx

chmod +x nginx
