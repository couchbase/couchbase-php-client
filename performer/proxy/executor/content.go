package executor

import (
	"encoding/base64"
	"errors"
	"fmt"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

func contentToInput(content *shared.Content) (*phpbackend.ContentInput, error) {
	if content == nil {
		return nil, nil
	}

	switch c := content.Content.(type) {
	case *shared.Content_PassthroughString:
		return &phpbackend.ContentInput{Kind: "passthrough_string", Value: &c.PassthroughString}, nil
	case *shared.Content_ConvertToJson:
		// Forwarded as text; see phpbackend.ContentInput.Json.
		return &phpbackend.ContentInput{Kind: "json", Json: string(c.ConvertToJson)}, nil
	case *shared.Content_ByteArray:
		return &phpbackend.ContentInput{Kind: "byte_array", Value: base64Value(c.ByteArray)}, nil
	case *shared.Content_Null:
		return &phpbackend.ContentInput{Kind: "null"}, nil
	default:
		return nil, errors.New("unknown content type")
	}
}

func rawBytes(content []byte) *phpbackend.ContentInput {
	return &phpbackend.ContentInput{
		Kind:  "byte_array",
		Value: base64Value(content),
	}
}

func contentAsToString(as *shared.ContentAs) string {
	if as == nil {
		return ""
	}

	switch as.As.(type) {
	case *shared.ContentAs_AsString:
		return "string"
	case *shared.ContentAs_AsByteArray:
		return "byte_array"
	case *shared.ContentAs_AsJsonObject:
		return "json_object"
	case *shared.ContentAs_AsJsonArray:
		return "json_array"
	case *shared.ContentAs_AsBoolean:
		return "boolean"
	case *shared.ContentAs_AsInteger:
		return "integer"
	case *shared.ContentAs_AsFloatingPoint:
		return "floating_point"
	default:
		return ""
	}
}

func contentPayloadToContentTypes(payload *phpbackend.ContentPayload) (*shared.ContentTypes, error) {
	if payload == nil {
		return nil, errors.New("php backend did not return content")
	}

	switch payload.Kind {
	case "bytes":
		data, err := base64.StdEncoding.DecodeString(payload.Bytes)
		if err != nil {
			return nil, fmt.Errorf("decoding content bytes from php backend: %w", err)
		}
		return &shared.ContentTypes{Content: &shared.ContentTypes_ContentAsBytes{ContentAsBytes: data}}, nil
	case "string":
		return &shared.ContentTypes{Content: &shared.ContentTypes_ContentAsString{ContentAsString: payload.String}}, nil
	case "int64":
		return &shared.ContentTypes{Content: &shared.ContentTypes_ContentAsInt64{ContentAsInt64: payload.Int64}}, nil
	case "double":
		return &shared.ContentTypes{Content: &shared.ContentTypes_ContentAsDouble{ContentAsDouble: payload.Double}}, nil
	case "bool":
		return &shared.ContentTypes{Content: &shared.ContentTypes_ContentAsBool{ContentAsBool: payload.Bool}}, nil
	case "null":
		// SDK null has its own protocol case; not the bytes "null".
		return &shared.ContentTypes{
			Content: &shared.ContentTypes_ContentAsNull{ContentAsNull: &shared.ContentTypes_NullValue{}},
		}, nil
	default:
		return nil, fmt.Errorf("unknown content kind %q from php backend", payload.Kind)
	}
}

func base64Value(b []byte) *string {
	encoded := base64.StdEncoding.EncodeToString(b)
	return &encoded
}
