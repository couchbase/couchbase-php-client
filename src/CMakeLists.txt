cmake_minimum_required(VERSION 3.17)
project(couchbase C CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_C_STANDARD 99)

include(${PROJECT_SOURCE_DIR}/cmake/revisions.cmake OPTIONAL)

add_subdirectory(deps/couchbase-cxx-client)

string(
  REGEX
  REPLACE "[\r\n]|-I"
          " "
          PHP_INCLUDE_DIRS
          "${COUCHBASE_PHP_INCLUDES}")
separate_arguments(PHP_INCLUDE_DIRS)

if(NOT EXT_GIT_REVISION)
  find_program(GIT git)
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
configure_file(${PROJECT_SOURCE_DIR}/cmake/ext_build_version.hxx.in
               ${PROJECT_BINARY_DIR}/generated/ext_build_version.hxx @ONLY)

file(GLOB SOURCE_FILES ${PROJECT_SOURCE_DIR}/core/*.cxx)
add_library(couchbase_php_core SHARED ${SOURCE_FILES})
target_include_directories(couchbase_php_core PRIVATE ${PROJECT_BINARY_DIR}/generated)
target_include_directories(couchbase_php_core PRIVATE BEFORE SYSTEM ${PHP_INCLUDE_DIRS})
target_link_libraries(couchbase_php_core PRIVATE project_options project_warnings couchbase_cxx_client)
if(APPLE)
  target_link_libraries(couchbase_php_core PRIVATE -Wl,-undefined,dynamic_lookup)
endif()
if(COUCHBASE_PHP_LIBDIR)
  add_custom_command(
    TARGET couchbase_php_core
    POST_BUILD
    COMMAND ${CMAKE_COMMAND} -E copy "$<TARGET_FILE:couchbase_php_core>"
            "${COUCHBASE_PHP_LIBDIR}/$<TARGET_FILE_NAME:couchbase_php_core>"
    VERBATIM)
endif()
