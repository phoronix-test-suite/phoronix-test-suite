#!/bin/sh


tar -xf reactor-core-3.3.5.RELEASE.tar.gz
cd reactor-core-3.3.5.RELEASE
sed -i -e s/http:/https:/ build.gradle
# build once to get all dependencies
./gradlew --parallel build -x test
cd ~

echo "#!/bin/sh
cd reactor-core-3.3.5.RELEASE
# Building offline to get consistent timing
./gradlew --parallel --offline clean build -x test > \$LOG_FILE
echo \$? > ~/test-exit-status" > java-gradle-perf
chmod +x java-gradle-perf
