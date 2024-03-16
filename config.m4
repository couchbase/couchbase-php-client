PHP_ARG_ENABLE([couchbase],
               [whether to enable Couchbase support],
               [AS_HELP_STRING([--enable-couchbase],
                               [Enable Couchbase support])])

AC_SUBST(PHP_COUCHBASE)

if test "$PHP_COUCHBASE" != "no"; then
  PHP_REQUIRE_CXX

  # PHP_REQUIRE_CXX macro might incorrectly format CXX variable,
  # concatenating standard selection flags directly to the path,
  # instead of using CXXFLAGS. Let's try to fix this issue.
  AC_CHECK_FILE([$CXX], [CXX_PATH=$CXX], [CXX_PATH=no])
  if test "$CXX_PATH" = "no"; then
    AC_MSG_NOTICE([PHP suggested C++ compiler, which includes flags "$CXX", trying to strip them])
    # Remove extra flags (considering flags are separated by spaces)
    CXX_PATH=$(echo "$CXX" | cut -d' ' -f1)
    AC_CHECK_FILE([$CXX_PATH], [], [CXX_PATH=no])
    # If a valid path is found, update CXX
    if test "$CXX_PATH" == "no"; then
      AC_MSG_NOTICE([Unable to locate path to C++ compiler, falling back to "c++"])
      AC_SUBST([CXX_PATH], [c++])
    fi
  fi
  AC_MSG_NOTICE([Detected C++ compiler: $CXX_PATH])

  AC_PATH_PROG(CMAKE, cmake, no)
  if ! test -x "${CMAKE}"; then
    AC_MSG_ERROR(Please install cmake to build couchbase extension)
  fi

  CXX="${CXX_PATH}"
  CXXFLAGS="${CXXFLAGS} -std=c++17"
  COUCHBASE_CMAKE_SOURCE_DIRECTORY="$srcdir/src"
  COUCHBASE_CMAKE_BUILD_DIRECTORY="$ac_pwd/cmake-build"

  PHP_SUBST([CMAKE])
  PHP_SUBST([COUCHBASE_CMAKE_SOURCE_DIRECTORY])
  PHP_SUBST([COUCHBASE_CMAKE_BUILD_DIRECTORY])

  PHP_NEW_EXTENSION(couchbase,, $ext_shared,,, cxx)
  PHP_ADD_EXTENSION_DEP(couchbase, json)
  PHP_ADD_BUILD_DIR($ext_builddir/src, 1)
  PHP_MODULES="\$(COUCHBASE_CMAKE_BUILD_DIRECTORY)/couchbase.\$(SHLIB_DL_SUFFIX_NAME)"
fi

PHP_ADD_MAKEFILE_FRAGMENT
