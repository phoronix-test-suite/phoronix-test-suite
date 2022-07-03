#!/bin/sh
unzip -o OSPRayStudio-Room-Scene-2.zip
tar -xf ospray_studio-0.11.0.tar.gz

cd ospray_studio-0.11.0
# Workarounds
sed -i 's/add_subdirectory(tests)/ /g' sg/CMakeLists.txt
echo "diff --git app/Batch.cpp.orig app/Batch.cpp
index 6546b26..3ab06e5 100644
--- app/Batch.cpp.orig
+++ app/Batch.cpp
@@ -215,6 +215,7 @@ bool BatchContext::parseCommandLine()
   int ac = studioCommon.argc;
   const char **av = studioCommon.argv;
 
+#if 0 // Allow Batch command-line arguments for Benchmark testing
   // if sgFile is being imported then disable BatchContext::addToCommandLine()
   if (ac > 1) {
     for (int i = 1; i < ac; ++i) {
@@ -232,6 +233,7 @@ bool BatchContext::parseCommandLine()
       }
     }
   }
+#endif
 
   std::shared_ptr<CLI::App> app = std::make_shared<CLI::App>(\"OSPRay Studio Batch\");
   StudioContext::addToCommandLine(app);

diff --git cmake/rkcommon.cmake.orig cmake/rkcommon.cmake
index 183ce9a..2f779be 100644
--- cmake/rkcommon.cmake.orig
+++ cmake/rkcommon.cmake
@@ -32,6 +32,9 @@ else()
     set(RKCOMMON_TBB_ROOT \${TBB_ROOT} CACHE INTERNAL \"ensure rkcommon finds dependent tbb\")
     set(BUILD_TESTING OFF CACHE INTERNAL \"disable testing for rkcommon\")
 
+    ## Build rkcommon as a static lib, to prevent conflict with rkcommon binary shipped with OSPRay
+    set(BUILD_SHARED_LIBS OFF CACHE BOOL \"\" FORCE)
+
     FetchContent_Declare(
         rkcommon
         GIT_REPOSITORY \"\${RKCOMMON_GIT_REPOSITORY}\"" > workaround.patch
patch -p0 < workaround.patch

mkdir build
cd build
cmake .. -DCMAKE_BUILD_TYPE=Release
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
export PATH=\$HOME/ospray_studio-0.11.0/build/:\$PATH

cd OSPRayStudio-Room-Scene/
ospStudio benchmark --denoiser --format jpg --forceRewrite \$@ RoomScene.sg > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ospray-studio
chmod +x ospray-studio
