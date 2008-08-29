#!/bin/sh

rm -rf shadow/
tar -xjf norsetto-shadow-01.tar.bz2
cd shadow/
make
cd ..

echo "#!/bin/sh
cd shadow/
./shadow --fps=1.0 --runlength=45.1 | grep \"Average FPS\"" > norsetto-shadow

chmod +x norsetto-shadow
