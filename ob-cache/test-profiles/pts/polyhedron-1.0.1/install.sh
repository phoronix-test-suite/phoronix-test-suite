#!/bin/sh

unzip -o pb11.zip
chmod +x pb11/lin/pbharness


echo "#!/bin/sh
cd pb11/lin/source
rm *.sum

echo \"\$1
7200 0.1 10 60
../pbvalid %r.run ../../f90valid.in>%r.chk
cat %r.chk>>%r.sum
cat %r.chk
@makearchive.par\" > pts_standard.par

if which gfortran >/dev/null 2>&1 ;
then
	echo \"gfortran -ffast-math -funroll-loops -O3 %n.f90 -o %n\" > pts_fortran_compiler.par
elif which f95 >/dev/null 2>&1 ;
then
	echo \"f95 -ffast-math -funroll-loops -O3 %n.f90 -o %n\" > pts_fortran_compiler.par
fi

../pbharness  pts_fortran_compiler pts_standard > \$LOG_FILE
echo \$? > ~/test-exit-status
cat *.sum >> \$LOG_FILE
" > polyhedron
chmod +x polyhedron
