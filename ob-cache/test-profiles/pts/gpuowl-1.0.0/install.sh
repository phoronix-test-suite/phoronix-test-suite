#!/bin/sh
unzip -o gpuowl-3567e66f82801d87adfca4d50c6ad3bb6c84a904.zip
cd gpuowl-3567e66f82801d87adfca4d50c6ad3bb6c84a904
echo "--- meson.build.orig	2024-01-05 16:46:16.124928049 -0500
+++ meson.build	2024-01-05 15:45:09.852237783 -0500
@@ -9,9 +9,9 @@
 version = vcs_tag(input:'version.inc.in', output:'version.inc')
 
 cpp = meson.get_compiler('cpp')
-amdocl = cpp.find_library('amdocl64', dirs:['/opt/rocm/lib'])
+#amdocl = cpp.find_library('amdocl64', dirs:['/opt/rocm/lib'])
 
-executable('gpuowl', sources: srcs + [gpuowl_wrap, version], dependencies:[amdocl, dependency('gmp')])
+executable('gpuowl', sources: srcs + [gpuowl_wrap, version], dependencies:[dependency('OpenCL'), dependency('gmp')])
 
 
 # Meson experiments below:
" > fix-build.patch
patch -p0 < fix-build.patch
mkdir build
cd build
meson setup --buildtype=release ..
ninja
echo $? > ~/install-exit-status
cd ~/
echo "#!/bin/sh
cd gpuowl-3567e66f82801d87adfca4d50c6ad3bb6c84a904/build
./gpuowl \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > gpuowl
chmod +x gpuowl
