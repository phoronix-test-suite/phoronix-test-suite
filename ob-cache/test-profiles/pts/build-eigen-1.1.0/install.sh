#!/bin/sh

echo "#!/bin/sh
cd eigen-3.3.9/doc/examples
COMP=\"c++  -DEIGEN_NO_EIGEN2_DEPRECATED_WARNING -DEIGEN_MAKING_DOCS -I. -I ../.. -c \"
for f in *.cpp
do
  echo \$COMP \$f
  \$COMP \$f
done  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > build-eigen

chmod +x build-eigen
