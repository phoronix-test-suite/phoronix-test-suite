#!/bin/sh

tar -xvf hpcc-1.3.1.tar.gz

cd hpcc-1.3.1
echo '#                                                                                                        
#  -- High Performance Computing Linpack Benchmark (HPL)                                                 
#     HPL - 2.0 - September 10, 2008                                                                     
#     Antoine P. Petitet                                                                                 
#     University of Tennessee, Knoxville                                                                 
#     Innovative Computing Laboratory                                                                    
#     (C) Copyright 2000-2008 All Rights Reserved                                                        
#                                                                                                        
#  -- Copyright notice and Licensing terms:                                                              
#                                                                                                        
#  Redistribution  and  use in  source and binary forms, with or without                                 
#  modification, are  permitted provided  that the following  conditions                                 
#  are met:                                                                                              
#                                                                                                        
#  1. Redistributions  of  source  code  must retain the above copyright                                 
#  notice, this list of conditions and the following disclaimer.                                         
#                                                                                                        
#  2. Redistributions in binary form must reproduce  the above copyright                                 
#  notice, this list of conditions,  and the following disclaimer in the                                 
#  documentation and/or other materials provided with the distribution.                                  
#                                                                                                        
#  3. All  advertising  materials  mentioning  features  or  use of this                                 
#  software must display the following acknowledgement:                                                  
#  This  product  includes  software  developed  at  the  University  of                                 
#  Tennessee, Knoxville, Innovative Computing Laboratory.                                                
#                                                                                                        
#  4. The name of the  University,  the name of the  Laboratory,  or the                                 
#  names  of  its  contributors  may  not  be used to endorse or promote                                 
#  products  derived   from   this  software  without  specific  written                                 
#  permission.                                                                                           
#                                                                                                        
#  -- Disclaimer:                                                                                        
#                                                                                                        
#  THIS  SOFTWARE  IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS                                 
#  ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,  INCLUDING,  BUT NOT                                 
#  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR                                 
#  A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE UNIVERSITY                                 
#  OR  CONTRIBUTORS  BE  LIABLE FOR ANY  DIRECT,  INDIRECT,  INCIDENTAL,                                 
#  SPECIAL,  EXEMPLARY,  OR  CONSEQUENTIAL DAMAGES  (INCLUDING,  BUT NOT                                 
#  LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,                                 
#  DATA OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY                                 
#  THEORY OF LIABILITY, WHETHER IN CONTRACT,  STRICT LIABILITY,  OR TORT                                 
#  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE                                 
#  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.                                  
# ######################################################################                                 
#                                                                                                        
# ----------------------------------------------------------------------                                 
# - shell --------------------------------------------------------------                                 
# ----------------------------------------------------------------------                                 
#                                                                                                        
SHELL        = /bin/sh
#                                                                                                        
CD           = cd
CP           = cp
LN_S         = ln -s
MKDIR        = mkdir
RM           = /bin/rm -f
TOUCH        = touch
#                                                                                                        
# ----------------------------------------------------------------------                                 
# - Platform identifier ------------------------------------------------                                 
# ----------------------------------------------------------------------                                 
#                                                                                                        
ARCH         = $(arch)
#                                                                                                        
# ----------------------------------------------------------------------                                 
# - HPL Directory Structure / HPL library ------------------------------                                 
# ----------------------------------------------------------------------                                 
#                                                                                                        
TOPdir       = ../../..
INCdir       = $(TOPdir)/include
BINdir       = $(TOPdir)/bin/$(ARCH)
LIBdir       = $(TOPdir)/lib/$(ARCH)
#                                                                                                        
HPLlib       = $(LIBdir)/libhpl.a
#                                                                                                        
# ----------------------------------------------------------------------                                 
# - MPI directories - library ------------------------------------------                                 
# ----------------------------------------------------------------------                                 
# MPinc tells the  C  compiler where to find the Message Passing library                                 
# header files,  MPlib  is defined  to be the name of  the library to be                                 
# used. The variable MPdir is only used for defining MPinc and MPlib.                                    
#                                                                                                        
MPdir        = 
MPinc        = 
MPlib        = 
#                                                                                                        
# ----------------------------------------------------------------------                                 
# - Linear Algebra library (BLAS or VSIPL) -----------------------------                                 
# ----------------------------------------------------------------------                                 
# LAinc tells the  C  compiler where to find the Linear Algebra  library                                 
# header files,  LAlib  is defined  to be the name of  the library to be                                 
# used. The variable LAdir is only used for defining LAinc and LAlib.                                    
#                                                                                                        
LAdir        = /usr/lib
LAinc        =
LAlib        = $(LAdir)/libcblas.a $(LAdir)/libatlas.a
#                                                                                                        
# ----------------------------------------------------------------------                                 
# - F77 / C interface --------------------------------------------------                                 
# ----------------------------------------------------------------------                                 
# You can skip this section  if and only if  you are not planning to use                                 
# a  BLAS  library featuring a Fortran 77 interface.  Otherwise,  it  is                                 
# necessary  to  fill out the  F2CDEFS  variable  with  the  appropriate                                 
# options.  **One and only one**  option should be chosen in **each** of                                 
# the 3 following categories:                                                                            
#                                                                                                        
# 1) name space (How C calls a Fortran 77 routine)                                                       
#                                                                                                        
# -DAdd_              : all lower case and a suffixed underscore  (Suns,                                 
#                       Intel, ...),                           [default]                                 
# -DNoChange          : all lower case (IBM RS6000),                                                     
# -DUpCase            : all upper case (Cray),                                                           
# -DAdd__             : the FORTRAN compiler in use is f2c.                                              
#                                                                                                        
# 2) C and Fortran 77 integer mapping                                                                    
#                                                                                                        
# -DF77_INTEGER=int   : Fortran 77 INTEGER is a C int,         [default]                                 
# -DF77_INTEGER=long  : Fortran 77 INTEGER is a C long,                                                  
# -DF77_INTEGER=short : Fortran 77 INTEGER is a C short.                                                 
#                                                                                                        
# 3) Fortran 77 string handling                                                                          
#                                                                                                        
# -DStringSunStyle    : The string address is passed at the string loca-                                 
#                       tion on the stack, and the string length is then                                 
#                       passed as  an  F77_INTEGER  after  all  explicit
#                       stack arguments,                       [default]
# -DStringStructPtr   : The address  of  a  structure  is  passed  by  a
#                       Fortran 77  string,  and the structure is of the
#                       form: struct {char *cp; F77_INTEGER len;},
# -DStringStructVal   : A structure is passed by value for each  Fortran
#                       77 string,  and  the  structure is  of the form:
#                       struct {char *cp; F77_INTEGER len;},
# -DStringCrayStyle   : Special option for  Cray  machines,  which  uses
#                       Cray  fcd  (fortran  character  descriptor)  for
#                       interoperation.
#
F2CDEFS      =
#
# ----------------------------------------------------------------------
# - HPL includes / libraries / specifics -------------------------------
# ----------------------------------------------------------------------
#
HPL_INCLUDES = -I$(INCdir) -I$(INCdir)/$(ARCH) $(LAinc) $(MPinc)
HPL_LIBS     = $(HPLlib) $(LAlib) $(MPlib) -lm
#
# - Compile time options -----------------------------------------------
#
# -DHPL_COPY_L           force the copy of the panel L before bcast;
# -DHPL_CALL_CBLAS       call the cblas interface;
# -DHPL_CALL_VSIPL       call the vsip  library;
# -DHPL_DETAILED_TIMING  enable detailed timers;
#
# By default HPL will:
#    *) not copy L before broadcast,
#    *) call the Fortran 77 BLAS interface
#    *) not display detailed timing information.
#
HPL_OPTS     = -DHPL_CALL_CBLAS
#
# ----------------------------------------------------------------------
#
HPL_DEFS     = $(F2CDEFS) $(HPL_OPTS) $(HPL_INCLUDES)
#
# ----------------------------------------------------------------------
# - Compilers / linkers - Optimization flags ---------------------------
# ----------------------------------------------------------------------
#
CC           = /usr/bin/mpicc.openmpi
CCNOOPT      = $(HPL_DEFS)
CCFLAGS      = $(HPL_DEFS) -fomit-frame-pointer -O3 -funroll-loops -W -Wall
#
LINKER       = /usr/bin/mpif77.openmpi
LINKFLAGS    = $(CCFLAGS)
#
ARCHIVER     = ar
ARFLAGS      = r
RANLIB       = echo
#
# ----------------------------------------------------------------------
' > hpl/Make.Linux
make arch=Linux

echo "HPLinpack benchmark input file
Innovative Computing Laboratory, University of Tennessee
HPL.out      output file name (if any)
8            device out (6=stdout,7=stderr,file)
1            # of problems sizes (N)
1000        Ns
1            # of NBs
80          NBs
0            PMAP process mapping (0=Row-,1=Column-major)
1            # of process grids (P x Q)
1            Ps
##NUM_CPU_CORES##	           Qs
16.0         threshold
1            # of panel fact
2            PFACTs (0=left, 1=Crout, 2=Right)
1            # of recursive stopping criterium
4            NBMINs (>= 1)
1            # of panels in recursion
2            NDIVs
1            # of recursive panel fact.
1            RFACTs (0=left, 1=Crout, 2=Right)
1            # of broadcast
1            BCASTs (0=1rg,1=1rM,2=2rg,3=2rM,4=Lng,5=LnM)
1            # of lookahead depth
1            DEPTHs (>=0)
2            SWAP (0=bin-exch,1=long,2=mix)
64           swapping threshold
0            L1 in (0=transposed,1=no-transposed) form
0            U  in (0=transposed,1=no-transposed) form
1            Equilibration (0=no,1=yes)
8            memory alignment in double (> 0)
##### This line (no. 32) is ignored (it serves as a separator). ######
0                               Number of additional problem sizes for PTRANS
1200 10000 30000                values of N
0                               number of additional blocking sizes for PTRANS
40 9 8 13 13 20 16 32 64        values of NB
" > _hpccinf.txt
cd ..
echo "#!/bin/sh
cd hpcc-1.3.1
rm -f hpccoutf.txt
cat _hpccinf.txt | sed -e s/##NUM_CPU_CORES##/\$NUM_CPU_CORES/ > hpccinf.txt
mpirun.openmpi -np \$NUM_CPU_CORES hpcc
echo \$? > ~/test-exit-status
cd ..
cp hpcc-1.3.1/hpccoutf.txt \$LOG_FILE
" > hpcc

chmod +x hpcc
