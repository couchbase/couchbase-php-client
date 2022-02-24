/**
 * Copyright 2016-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#include "version.hxx"

#include <couchbase/meta/version.hxx>

namespace couchbase::php
{
zval
core_version()
{
    zval version;
    array_init(&version);

    for (const auto& [name, value] : couchbase::meta::sdk_build_info()) {
        if (name == "version_major" || name == "version_minor" || name == "version_patch" || name == "version_build") {
            add_assoc_long_ex(&version, name.c_str(), name.size(), std::stoi(value));
        } else if (name == "snapshot" || name == "static_stdlib" || name == "static_openssl") {
            add_assoc_bool_ex(&version, name.c_str(), name.size(), value == "true");
        } else {
            add_assoc_stringl_ex(&version, name.c_str(), name.size(), value.c_str(), value.size());
        }
    }

    return version;
}
} // namespace couchbase::php