#!/bin/sh

echo "#!/bin/sh
cd eigen-eigen-bdd17ee3b1b3/doc/examples
[ -z \$CXX ] && CXX=\"g++\"
COMP=\"${CXX}  -DEIGEN2_SUPPORT= -DEIGEN_NO_EIGEN2_DEPRECATED_WARNING -DEIGEN_MAKING_DOCS -I. -I ../.. -c \"
for f in *.cpp
do
  echo \$COMP \$f
  \$COMP \$f
done  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > build-eigen

chmod +x build-eigen
