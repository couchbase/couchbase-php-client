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
#include "ext_build_version.hxx"

#include <couchbase/meta/version.hxx>

namespace couchbase::php
{
void
core_version(zval* return_value)
{
    array_init(return_value);

    add_assoc_string(return_value, "extension_revision", COUCHBASE_EXTENSION_GIT_REVISION);
    add_assoc_string(return_value, "cxx_client_revision", COUCHBASE_CXX_CLIENT_GIT_REVISION);
    add_assoc_string(return_value, "cxx_transactions_revision", COUCHBASE_CXX_TRANSACTIONS_GIT_REVISION);

    for (const auto& [name, value] : couchbase::meta::sdk_build_info()) {
        if (name == "version_major" || name == "version_minor" || name == "version_patch" || name == "version_build") {
            add_assoc_long_ex(return_value, name.c_str(), name.size(), std::stoi(value));
        } else if (name == "snapshot" || name == "static_stdlib" || name == "static_openssl") {
            add_assoc_bool_ex(return_value, name.c_str(), name.size(), value == "true");
        } else {
            add_assoc_stringl_ex(return_value, name.c_str(), name.size(), value.c_str(), value.size());
        }
    }
}

const char*
extension_revision()
{
    return COUCHBASE_EXTENSION_GIT_REVISION;
}

const char*
cxx_client_revision()
{
    return COUCHBASE_CXX_CLIENT_GIT_REVISION;
}

const char*
cxx_transactions_revision()
{
    return COUCHBASE_CXX_TRANSACTIONS_GIT_REVISION;
}
} // namespace couchbase::php