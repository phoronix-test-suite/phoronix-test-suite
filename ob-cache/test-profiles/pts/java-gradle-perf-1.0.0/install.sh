#!/bin/sh


tar -xf reactor-core-2.5.0m1.tar.xz
cd reactor-core
# build once to get all dependencies
./gradlew build
cd ~

echo "#!/bin/sh
cd reactor-core
# Building offline to get consistent timing
./gradlew --offline clean build > \$LOG_FILE
echo \$? > ~/test-exit-status" > java-gradle-perf
chmod +x java-gradle-perf
