#!/bin/sh

tar -xvf jxrender-0.6.tar.gz

echo "#!/bin/sh
cd rendermark/
./JXRenderMark-0.6 \$@ > \$LOG_FILE 2>&1" > jxrender-run
chmod +x jxrender-run
