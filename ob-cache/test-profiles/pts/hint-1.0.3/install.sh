#!/bin/sh

tar -xzf hint-1.0.tar.gz
cd unix

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

cc $CFLAGS hint.c hkernel.c -Dunix -DDOUBLE -DIINT -o DOUBLE -lm
cc $CFLAGS hint.c hkernel.c -Dunix -DINT -DIINT -o INT -lm
cc $CFLAGS hint.c hkernel.c -Dunix -DFLOAT -DIINT -o FLOAT -lm
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd unix/
./\$@ > \$LOG_FILE 2>&1" > hint
chmod +x hint
