#!/usr/bin/env ruby
# frozen_string_literal: true

require "fileutils"
require "nokogiri"
require "tempfile"
require "rubygems/package"

class Object
  def to_b
    ![nil, false, 0, "", "0", "f", "F", "false", "FALSE", "off", "OFF", "no", "NO"].include?(self)
  end
end

def run(*args)
  args = args.compact.map(&:to_s)
  puts args.join(" ")
  system(*args) || abort("command returned non-zero status: #{args.join(' ')}")
end

def which(name, extra_locations = [])
  ENV.fetch("PATH", "")
     .split(File::PATH_SEPARATOR)
     .prepend(*extra_locations)
     .select { |path| File.directory?(path) }
     .map { |path| [path, name].join(File::SEPARATOR) + RbConfig::CONFIG["EXEEXT"] }
     .find { |file| File.executable?(file) }
end

project_root = File.expand_path(File.join(__dir__, ".."))
cxx_core_source_dir = File.join(project_root, "src", "deps", "couchbase-cxx-client")

library_revision = Dir.chdir(project_root) { `git rev-parse HEAD`.strip }
core_revision = Dir.chdir(cxx_core_source_dir) { `git rev-parse HEAD`.strip }
core_describe = Dir.chdir(cxx_core_source_dir) { `git describe --long --always HEAD`.strip }

# cache dependencies
output_dir = Dir.mktmpdir("cxx_output_")
output_tarball = File.join(output_dir, "cache.tar")
cpm_cache_dir = Dir.mktmpdir("cxx_cache_")
cxx_core_build_dir =  Dir.mktmpdir("cxx_build_")
cc = ENV.fetch("CB_CC", nil)
cxx = ENV.fetch("CB_CXX", nil)
ar = ENV.fetch("CB_AR", nil)

cmake_extra_locations = []
if RUBY_PLATFORM.match?(/mswin|mingw/)
  cmake_extra_locations = [
    'C:\Program Files\CMake\bin',
    'C:\Program Files\Microsoft Visual Studio\2022\Professional\Common7\IDE\CommonExtensions\Microsoft\CMake\CMake\bin',
    'C:\Program Files\Microsoft Visual Studio\2019\Professional\Common7\IDE\CommonExtensions\Microsoft\CMake\CMake\bin',
  ]
  local_app_data = ENV.fetch("LOCALAPPDATA", "#{Dir.home}\\AppData\\Local")
  cmake_extra_locations.unshift("#{local_app_data}\\CMake\\bin") if File.directory?(local_app_data)
  cc = RbConfig::CONFIG["CC"]
  cxx = RbConfig::CONFIG["CXX"]
end
cmake = which("cmake", cmake_extra_locations) || which("cmake3", cmake_extra_locations)
cmake_flags = [
  "-S#{cxx_core_source_dir}",
  "-B#{cxx_core_build_dir}",
  "-DCOUCHBASE_CXX_CLIENT_BUILD_TESTS=OFF",
  "-DCOUCHBASE_CXX_CLIENT_BUILD_TOOLS=OFF",
  "-DCOUCHBASE_CXX_CLIENT_BUILD_DOCS=OFF",
  "-DCOUCHBASE_CXX_CLIENT_STATIC_BORINGSSL=ON",
  "-DCPM_DOWNLOAD_ALL=ON",
  "-DCPM_USE_NAMED_CACHE_DIRECTORIES=ON",
  "-DCPM_USE_LOCAL_PACKAGES=OFF",
  "-DCPM_SOURCE_CACHE=#{cpm_cache_dir}",
]
cmake_flags << "-DCMAKE_C_COMPILER=#{cc}" if cc
cmake_flags << "-DCMAKE_CXX_COMPILER=#{cxx}" if cxx
cmake_flags << "-DCMAKE_AR=#{ar}" if ar

puts("-----> run cmake to dowload all depenencies (#{cmake})")
run(cmake, *cmake_flags)

puts("-----> create archive with whitelisted sources: #{output_tarball}")
File.open(output_tarball, "w+b") do |file|
  Gem::Package::TarWriter.new(file) do |writer|
    Dir.chdir(cxx_core_build_dir) do
      ["mozilla-ca-bundle.sha256", "mozilla-ca-bundle.crt"].each do |path|
        writer.add_file(path, 0o660) { |io| io.write(File.binread(path)) }
      end
    end
    Dir.chdir(cpm_cache_dir) do
      third_party_sources = Dir[
        "cpm/*.cmake",
        "asio/*/LICENSE*",
        "asio/*/asio/COPYING",
        "asio/*/asio/asio/include/*.hpp",
        "asio/*/asio/asio/include/asio/**/*.[hi]pp",
        "boringssl/*/boringssl/**/*.{cc,h,c,asm,S}",
        "boringssl/*/boringssl/**/CMakeLists.txt",
        "boringssl/*/boringssl/LICENSE",
        "fmt/*/fmt/CMakeLists.txt",
        "fmt/*/fmt/ChangeLog.md",
        "fmt/*/fmt/LICENSE.md",
        "fmt/*/fmt/README.md",
        "fmt/*/fmt/include/**/*",
        "fmt/*/fmt/src/**/*",
        "fmt/*/fmt/support/cmake/**/*",
        "gsl/*/gsl/CMakeLists.txt",
        "gsl/*/gsl/GSL.natvis",
        "gsl/*/gsl/LICENSE*",
        "gsl/*/gsl/ThirdPartyNotices.txt",
        "gsl/*/gsl/cmake/*",
        "gsl/*/gsl/include/**/*",
        "hdr_histogram/*/hdr_histogram/*.pc.in",
        "hdr_histogram/*/hdr_histogram/CMakeLists.txt",
        "hdr_histogram/*/hdr_histogram/COPYING.txt",
        "hdr_histogram/*/hdr_histogram/LICENSE.txt",
        "hdr_histogram/*/hdr_histogram/cmake/*",
        "hdr_histogram/*/hdr_histogram/config.cmake.in",
        "hdr_histogram/*/hdr_histogram/include/**/*",
        "hdr_histogram/*/hdr_histogram/src/**/*",
        "json/*/json/CMakeLists.txt",
        "json/*/json/LICENSE*",
        "json/*/json/external/PEGTL/.cmake/*",
        "json/*/json/external/PEGTL/CMakeLists.txt",
        "json/*/json/external/PEGTL/LICENSE*",
        "json/*/json/external/PEGTL/include/**/*",
        "json/*/json/include/**/*",
        "llhttp/*/llhttp/*.pc.in",
        "llhttp/*/llhttp/CMakeLists.txt",
        "llhttp/*/llhttp/LICENSE*",
        "llhttp/*/llhttp/include/*.h",
        "llhttp/*/llhttp/src/*.c",
        "snappy/*/snappy/CMakeLists.txt",
        "snappy/*/snappy/COPYING",
        "snappy/*/snappy/cmake/*",
        "snappy/*/snappy/snappy-c.{h,cc}",
        "snappy/*/snappy/snappy-internal.h",
        "snappy/*/snappy/snappy-sinksource.{h,cc}",
        "snappy/*/snappy/snappy-stubs-internal.{h,cc}",
        "snappy/*/snappy/snappy-stubs-public.h.in",
        "snappy/*/snappy/snappy.{h,cc}",
        "spdlog/*/spdlog/CMakeLists.txt",
        "spdlog/*/spdlog/LICENSE",
        "spdlog/*/spdlog/cmake/*",
        "spdlog/*/spdlog/include/**/*",
        "spdlog/*/spdlog/src/**/*",
      ].grep_v(/crypto_test_data.cc/)

      # we don't want to fail if git is not available
      cpm_cmake_path = third_party_sources.grep(/cpm.*\.cmake$/).first
      File.write(cpm_cmake_path, File.read(cpm_cmake_path).gsub("Git REQUIRED", "Git"))

      third_party_sources
        .select { |path| File.file?(path) }
        .each { |path| writer.add_file(path, 0o660) { |io| io.write(File.binread(path)) } }
    end
  end
end

FileUtils.rm_rf(cxx_core_build_dir, verbose: true)
FileUtils.rm_rf(cpm_cache_dir, verbose: true)

untar = ["tar", "-x"]
untar << "--force-local" unless RUBY_PLATFORM.include?("darwin")

puts("-----> verify that tarball works as a cache for CPM")
cxx_core_build_dir = Dir.mktmpdir("cxx_build_")
cpm_cache_dir = Dir.mktmpdir("cxx_cache_")
Dir.chdir(cpm_cache_dir) do
  run(*untar, "-f", output_tarball)
end

cmake_flags = [
  "-S#{cxx_core_source_dir}",
  "-B#{cxx_core_build_dir}",
  "-DCOUCHBASE_CXX_CLIENT_BUILD_TESTS=OFF",
  "-DCOUCHBASE_CXX_CLIENT_BUILD_TOOLS=OFF",
  "-DCOUCHBASE_CXX_CLIENT_BUILD_DOCS=OFF",
  "-DCOUCHBASE_CXX_CLIENT_STATIC_BORINGSSL=ON",
  "-DCPM_DOWNLOAD_ALL=OFF",
  "-DCPM_USE_NAMED_CACHE_DIRECTORIES=ON",
  "-DCPM_USE_LOCAL_PACKAGES=OFF",
  "-DCPM_SOURCE_CACHE=#{cpm_cache_dir}",
  "-DCOUCHBASE_CXX_CLIENT_EMBED_MOZILLA_CA_BUNDLE_ROOT=#{cpm_cache_dir}",
]
cmake_flags << "-DCMAKE_C_COMPILER=#{cc}" if cc
cmake_flags << "-DCMAKE_CXX_COMPILER=#{cxx}" if cxx
cmake_flags << "-DCMAKE_AR=#{ar}" if ar

run(cmake, *cmake_flags)

FileUtils.rm_rf(cxx_core_build_dir, verbose: true)
FileUtils.rm_rf(cpm_cache_dir, verbose: true)

cache_dir = File.join(__dir__, "..", "src", "deps", "cache")
FileUtils.rm_rf(cache_dir, verbose: true)
abort("unable to remove #{cache_dir}") if File.directory?(cache_dir)
FileUtils.mkdir_p(cache_dir, verbose: true)
Dir.chdir(cache_dir) do
  run(*untar, "-f", output_tarball)
end
FileUtils.rm_rf(output_dir, verbose: true)

File.write(File.join(project_root, "src", "cmake", "pecl_package.cmake"), <<~PECL_PACKAGE_CMAKE)
  set(EXT_GIT_REVISION #{library_revision.inspect} CACHE STRING "" FORCE)
  set(COUCHBASE_CXX_CLIENT_GIT_REVISION #{core_revision.inspect} CACHE STRING "" FORCE)
  set(COUCHBASE_CXX_CLIENT_GIT_DESCRIBE #{core_describe.inspect} CACHE STRING "" FORCE)
  set(CPM_DOWNLOAD_ALL OFF CACHE BOOL "" FORCE)
  set(CPM_USE_NAMED_CACHE_DIRECTORIES OFF CACHE BOOL "" FORCE)
  set(CPM_USE_LOCAL_PACKAGES OFF CACHE BOOL "" FORCE)
  set(CPM_SOURCE_CACHE "${PROJECT_SOURCE_DIR}/deps/cache" CACHE STRING "" FORCE)
  set(COUCHBASE_CXX_CLIENT_EMBED_MOZILLA_CA_BUNDLE_ROOT "${PROJECT_SOURCE_DIR}/deps/cache" CACHE STRING "" FORCE)
PECL_PACKAGE_CMAKE

package_xml_in_path = File.join(project_root, "package.xml.in")
package_xml_out_path = File.join(project_root, "package.xml")
package_xml = Nokogiri::XML.parse(File.read(package_xml_in_path), &:noblanks)

files = [
  "LICENSE",
  "GPBMetadata/**/*.php",
  "Couchbase/**/*.php",
  "Makefile.frag",
  "config.m4",
  "config.w32",
  "src/*.{cxx,hxx}",
  "src/CMakeLists.txt",
  "src/cmake/*",
  "src/deps/cache/**/*",
  "src/deps/couchbase-cxx-client/CMakeLists.txt",
  "src/deps/couchbase-cxx-client/LICENSE.txt",
  "src/deps/couchbase-cxx-client/cmake/*",
  "src/deps/couchbase-cxx-client/core/**/*",
  "src/deps/couchbase-cxx-client/couchbase/**/*",
  "src/deps/couchbase-cxx-client/third_party/cxx_function/cxx_function.hpp",
  "src/deps/couchbase-cxx-client/third_party/expected/COPYING",
  "src/deps/couchbase-cxx-client/third_party/expected/include/**/*",
  "src/deps/couchbase-cxx-client/third_party/jsonsl/*",
  "src/wrapper/**/*.{cxx,hxx}",
].map do |glob|
  Dir.chdir(project_root) do
    Dir.glob(glob, File::FNM_DOTMATCH).select { |path| File.file?(path) }
  end
end.flatten

tree = {directories: {}, files: []}
files.sort.uniq.each do |file|
  parts = file.split("/")
  parents = parts[0..-2]
  filename = parts[-1]
  cursor = tree
  parents.each do |parent|
    cursor[:directories][parent] ||= {directories: {}, files: []}
    cursor = cursor[:directories][parent]
  end
  role =
    case filename
    when /\.php$/
      "php"
    when /README|LICENSE|COPYING/
      "doc"
    else
      "src"
    end
  role = "src" if filename == "README.rst" && parents.last == "fmt"
  cursor[:files] << {name: filename, role: role}
end

def traverse(document, reader, writer)
  reader[:directories].each do |name, dir|
    node = document.create_element("dir")
    node["name"] = name
    writer.add_child(node)
    traverse(document, dir, node)
  end
  reader[:files].each do |file|
    node = document.create_element("file")
    node["role"] = file[:role]
    node["name"] = file[:name]
    writer.add_child(node)
  end
end

root = package_xml.create_element("dir")
root["name"] = "/"
traverse(package_xml, tree, root)

package_xml.at_css("package date").children = Time.now.utc.strftime("%Y-%m-%d")
package_xml.at_css("package contents").children = root
File.write(package_xml_out_path, package_xml.to_xml(indent: 4))
File.join(project_root, "package.xml")

Dir.chdir(project_root) do
  run("pecl package")

  main_header = File.read(File.join(project_root, "src/php_couchbase.hxx"))
  sdk_version = main_header[/PHP_COUCHBASE_VERSION "(\d+\.\d+\.\d+)"/, 1]
  snapshot = ENV.fetch("BUILD_NUMBER", 0).to_i
  if snapshot > 0
    FileUtils.mv("couchbase-#{sdk_version}.tgz", "couchbase-#{sdk_version}.#{snapshot}.tgz", verbose: true)
  end
end
