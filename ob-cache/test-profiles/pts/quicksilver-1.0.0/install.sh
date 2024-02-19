#!/bin/sh
unzip -o Quicksilver-eb68bb8d6fc53de1f65011d4e79ff2ed0dd60f3b.zip
cd Quicksilver-eb68bb8d6fc53de1f65011d4e79ff2ed0dd60f3b
patch -p0 <<EOF
diff -Naur src.orig/EnergySpectrum.hh src/EnergySpectrum.hh
--- src.orig/EnergySpectrum.hh	2023-08-18 18:12:10.000000000 -0400
+++ src/EnergySpectrum.hh	2024-01-06 17:22:10.689220407 -0500
@@ -2,6 +2,7 @@
 #define ENERGYSPECTRUM_HH
 #include <string>
 #include <vector>
+#include <cstdint>
 
 class MonteCarlo;
 
diff -Naur src.orig/Makefile src/Makefile
--- src.orig/Makefile	2023-08-18 18:12:10.000000000 -0400
+++ src/Makefile	2024-01-06 17:20:13.178478861 -0500
@@ -108,11 +108,11 @@
 #LDFLAGS = -L$(ROCM_ROOT)/lib -lamdhip64
 
 #AMD with HIP
-ROCM_ROOT = /opt/rocm-5.6.0
-CXX = /usr/tce/packages/cray-mpich/cray-mpich-8.1.26-rocmcc-5.6.0-cce-16.0.0a-magic/bin/mpicxx
-CXXFLAGS = -g 
-CPPFLAGS = -DHAVE_MPI -DHAVE_HIP -x hip --offload-arch=gfx90a -fgpu-rdc -Wno-unused-result
-LDFLAGS = -fgpu-rdc --hip-link --offload-arch=gfx90a
+#ROCM_ROOT = /opt/rocm-5.6.0
+#CXX = /usr/tce/packages/cray-mpich/cray-mpich-8.1.26-rocmcc-5.6.0-cce-16.0.0a-magic/bin/mpicxx
+#CXXFLAGS = -g 
+#CPPFLAGS = -DHAVE_MPI -DHAVE_HIP -x hip --offload-arch=gfx90a -fgpu-rdc -Wno-unused-result
+#LDFLAGS = -fgpu-rdc --hip-link --offload-arch=gfx90a
 
 
 
diff -Naur src.orig/MC_Cell_State.hh src/MC_Cell_State.hh
--- src.orig/MC_Cell_State.hh	2023-08-18 18:12:10.000000000 -0400
+++ src/MC_Cell_State.hh	2024-01-06 17:21:14.547964637 -0500
@@ -4,7 +4,7 @@
 #include <cstdio>
 #include "QS_Vector.hh"
 #include "macros.hh"
-
+#include <cstdint>
 
 // this stores all the material information on a cell
 class MC_Cell_State
diff -Naur src.orig/NuclearData.hh src/NuclearData.hh
--- src.orig/NuclearData.hh	2023-08-18 18:12:10.000000000 -0400
+++ src/NuclearData.hh	2024-01-06 17:22:57.142247106 -0500
@@ -9,6 +9,7 @@
 #include <algorithm>
 #include "qs_assert.hh"
 #include "DeclareMacro.hh"
+#include <cstdint>
 
 class Polynomial
 {
diff -Naur src.orig/Parameters.hh src/Parameters.hh
--- src.orig/Parameters.hh	2023-08-18 18:12:10.000000000 -0400
+++ src/Parameters.hh	2024-01-06 17:21:39.596528851 -0500
@@ -8,6 +8,8 @@
 #include <vector>
 #include <map>
 #include <iostream>
+#include <cstdint>
+
 
 struct GeometryParameters
 {

EOF
cd src
CXX=c++ CXXFLAGS="-DHAVE_OPENMP -fopenmp -O3 -march=native" make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd Quicksilver-eb68bb8d6fc53de1f65011d4e79ff2ed0dd60f3b/src
./qs -i \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > quicksilver
chmod +x quicksilver
