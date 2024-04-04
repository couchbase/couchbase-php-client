cmake_minimum_required(VERSION 3.27)
if(POLICY CMP0149)
  # BoringSSL requires at least WindowsSDKVersion=10.0.20348.0
  cmake_policy(SET CMP0149 NEW)
endif()

project(couchbase C CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_C_STANDARD 99)

include(${PROJECT_SOURCE_DIR}/cmake/pecl_package.cmake OPTIONAL)

set(COUCHBASE_CXX_CLIENT_BUILD_DOCS
    OFF
    CACHE BOOL "" FORCE)
set(COUCHBASE_CXX_CLIENT_BUILD_EXAMPLES
    OFF
    CACHE BOOL "" FORCE)
set(COUCHBASE_CXX_CLIENT_BUILD_TESTS
    OFF
    CACHE BOOL "" FORCE)
set(COUCHBASE_CXX_CLIENT_BUILD_TOOLS
    OFF
    CACHE BOOL "" FORCE)
set(COUCHBASE_CXX_CLIENT_BUILD_SHARED
    OFF
    CACHE BOOL "" FORCE)
set(COUCHBASE_CXX_CLIENT_STATIC_BORINGSSL
    ON
    CACHE BOOL "" FORCE)
set(CMAKE_POSITION_INDEPENDENT_CODE ON)

add_subdirectory(deps/couchbase-cxx-client)

string(
  REGEX
  REPLACE "[\r\n]| *-I| */I"
          " "
          PHP_INCLUDE_DIRS
          "${COUCHBASE_PHP_INCLUDES}")
separate_arguments(PHP_INCLUDE_DIRS)
message(STATUS "PHP_INCLUDE_DIRS: ${PHP_INCLUDE_DIRS}")
separate_arguments(COUCHBASE_PHP_CFLAGS)
message(STATUS "COUCHBASE_PHP_CFLAGS: ${COUCHBASE_PHP_CFLAGS}")

find_program(GIT git)
if(NOT EXT_GIT_REVISION)
  if(GIT)
    execute_process(
      COMMAND git rev-parse HEAD
      WORKING_DIRECTORY ${PROJECT_SOURCE_DIR}
      OUTPUT_STRIP_TRAILING_WHITESPACE
      OUTPUT_VARIABLE EXT_GIT_REVISION)
  else()
    set(EXT_GIT_REVISION "unknown")
  endif()
endif()
if(NOT COUCHBASE_CXX_CLIENT_GIT_REVISION)
  if(GIT)
    execute_process(
      COMMAND git rev-parse HEAD
      WORKING_DIRECTORY ${PROJECT_SOURCE_DIR}/deps/couchbase-cxx-client
      OUTPUT_STRIP_TRAILING_WHITESPACE
      OUTPUT_VARIABLE COUCHBASE_CXX_CLIENT_GIT_REVISION)
  else()
    set(COUCHBASE_CXX_CLIENT_GIT_REVISION "unknown")
  endif()
endif()
configure_file(${PROJECT_SOURCE_DIR}/cmake/ext_build_version.hxx.in
               ${PROJECT_BINARY_DIR}/generated/ext_build_version.hxx @ONLY)

file(
  GLOB
  SOURCE_FILES
  ${PROJECT_SOURCE_DIR}/wrapper/*.cxx
  ${PROJECT_SOURCE_DIR}/php_couchbase.cxx)
add_library(couchbase SHARED ${SOURCE_FILES})
set_target_properties(couchbase PROPERTIES PREFIX "")
if(APPLE)
  set_target_properties(couchbase PROPERTIES SUFFIX ".so")
endif()
if(MSVC)
  set_target_properties(couchbase PROPERTIES PREFIX "php_" IMPORT_PREFIX "php_")
endif()
target_include_directories(couchbase PRIVATE ${PROJECT_BINARY_DIR}/generated)
target_include_directories(couchbase PRIVATE ${PROJECT_SOURCE_DIR}/deps/couchbase-cxx-client)
target_include_directories(couchbase PRIVATE ${PHP_INCLUDE_DIRS})
target_compile_definitions(couchbase PRIVATE ZEND_COMPILE_DL_EXT=1)
set_target_properties(couchbase_cxx_client PROPERTIES C_VISIBILITY_PRESET hidden CXX_VISIBILITY_PRESET hidden)
target_link_libraries(
  couchbase
  PRIVATE project_options
          project_warnings
          couchbase_cxx_client
          spdlog::spdlog
          taocpp::json
          fmt::fmt
          asio
          Microsoft.GSL::GSL)
if(APPLE)
  target_link_libraries(couchbase PRIVATE -Wl,-undefined,dynamic_lookup)
endif()
if(WIN32)
  target_compile_options(couchbase PRIVATE ${COUCHBASE_PHP_CFLAGS} /bigobj)
  target_compile_definitions(couchbase PRIVATE NOMINMAX)
  target_link_options(couchbase PUBLIC "${COUCHBASE_PHP_LIB}")
endif()
if(COUCHBASE_PHP_LIBDIR)
  add_custom_command(
    TARGET couchbase
    POST_BUILD
    COMMAND ${CMAKE_COMMAND} -E copy "$<TARGET_FILE:couchbase>" "$<TARGET_LINKER_FILE:couchbase>"
            "${COUCHBASE_PHP_LIBDIR}/"
    VERBATIM)
endif()
