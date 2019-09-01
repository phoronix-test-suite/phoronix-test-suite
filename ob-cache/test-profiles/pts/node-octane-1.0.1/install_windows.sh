#!/bin/sh

unzip -o benchmark-octane-20181001.zip

cd ~
echo "#!/bin/sh
cd benchmark-octane-master
node run.js > \$LOG_FILE 2>&1
echo \"Nodejs \" > ~/pts-footnote
node --version >> ~/pts-footnote 2>/dev/null" > node-octane
chmod +x node-octane
