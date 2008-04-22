#!/bin/sh

cd $1

if [ ! -f norsetto-shadow-01.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/norsetto-shadow-01.tar.bz2 -O norsetto-shadow-01.tar.bz2
fi

rm -rf shadow/
tar -xjf norsetto-shadow-01.tar.bz2
cd shadow/
make
cd ..

echo "#!/bin/sh
cd shadow/
./shadow --fps=1.0 --runlength=45.1 | grep \"Average FPS\"" > norsetto-shadow

chmod +x norsetto-shadow
