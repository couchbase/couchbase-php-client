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

#pragma once

#include <couchbase/codec/codec_flags.hxx>
#include <couchbase/codec/encoded_value.hxx>
#include <couchbase/codec/transcoder_traits.hxx>

namespace couchbase
{
namespace php
{

struct passthrough_transcoder {
    using document_type = codec::encoded_value;

    static auto decode(const codec::encoded_value& data) -> document_type
    {
        return data;
    }

    static auto encode(codec::encoded_value document) -> codec::encoded_value
    {
        return document;
    }
};
} // namespace php

template<>
struct codec::is_transcoder<php::passthrough_transcoder> : public std::true_type {
};
} // namespace couchbase
