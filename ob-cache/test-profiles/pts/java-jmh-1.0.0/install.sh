#!/bin/sh

mvn archetype:generate \
          -DinteractiveMode=false \
          -DarchetypeGroupId=org.openjdk.jmh \
          -DarchetypeArtifactId=jmh-java-benchmark-archetype \
          -DgroupId=org.sample \
          -DartifactId=test \
          -Dversion=1.0
cd test
mvn clean install
cd ~

echo "#!/bin/sh
cd test
java -jar target/benchmarks.jar  -t max > \$LOG_FILE 2>&1" > java-jmh
chmod +x java-jmh
