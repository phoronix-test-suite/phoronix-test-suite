#!/bin/sh

echo "#!/bin/sh
cd boost_1_59_0/libs/interprocess/example
[ -z \$CXX ] && CXX=\"g++\"
COMP=\"${CXX} -std=c++11 -I ../../.. -c \"
for f in *.cpp
do
  echo \$COMP \$f
  \$COMP \$f
done  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > build-boost-interprocess

chmod +x build-boost-interprocess
