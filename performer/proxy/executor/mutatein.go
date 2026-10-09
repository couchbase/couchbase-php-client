package executor

import (
	"fmt"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/collection/mutatein"
)

// Specs keep protobuf order: the driver asserts on per-index results.

func mutateInSpecs(specs []*mutatein.MutateInSpec) ([]phpbackend.MutateInSpec, error) {
	out := make([]phpbackend.MutateInSpec, 0, len(specs))

	for _, spec := range specs {
		wire, err := mutateInSpec(spec)
		if err != nil {
			return nil, err
		}
		out = append(out, wire)
	}

	return out, nil
}

func mutateInSpec(spec *mutatein.MutateInSpec) (phpbackend.MutateInSpec, error) {
	out := phpbackend.MutateInSpec{
		// Empty means don't read back, which differs from the default type.
		ContentAs: contentAsToString(spec.ContentAs),
	}

	switch op := spec.Operation.(type) {
	case *mutatein.MutateInSpec_Upsert:
		out.Kind = "upsert"
		out.Path = op.Upsert.Path
		out.Xattr = op.Upsert.Xattr
		out.CreatePath = op.Upsert.CreatePath
		content, err := contentOrMacro(op.Upsert.Content)
		if err != nil {
			return out, err
		}
		out.Content = content

	case *mutatein.MutateInSpec_Insert:
		out.Kind = "insert"
		out.Path = op.Insert.Path
		out.Xattr = op.Insert.Xattr
		out.CreatePath = op.Insert.CreatePath
		content, err := contentOrMacro(op.Insert.Content)
		if err != nil {
			return out, err
		}
		out.Content = content

	case *mutatein.MutateInSpec_Replace:
		out.Kind = "replace"
		out.Path = op.Replace.Path
		out.Xattr = op.Replace.Xattr
		content, err := contentOrMacro(op.Replace.Content)
		if err != nil {
			return out, err
		}
		out.Content = content

	case *mutatein.MutateInSpec_Remove:
		out.Kind = "remove"
		out.Path = op.Remove.Path
		out.Xattr = op.Remove.Xattr

	case *mutatein.MutateInSpec_ArrayAppend:
		out.Kind = "array_append"
		out.Path = op.ArrayAppend.Path
		out.Xattr = op.ArrayAppend.Xattr
		out.CreatePath = op.ArrayAppend.CreatePath
		contents, err := contentsOrMacros(op.ArrayAppend.Content)
		if err != nil {
			return out, err
		}
		out.Contents = contents

	case *mutatein.MutateInSpec_ArrayPrepend:
		out.Kind = "array_prepend"
		out.Path = op.ArrayPrepend.Path
		out.Xattr = op.ArrayPrepend.Xattr
		out.CreatePath = op.ArrayPrepend.CreatePath
		contents, err := contentsOrMacros(op.ArrayPrepend.Content)
		if err != nil {
			return out, err
		}
		out.Contents = contents

	case *mutatein.MutateInSpec_ArrayInsert:
		out.Kind = "array_insert"
		out.Path = op.ArrayInsert.Path
		out.Xattr = op.ArrayInsert.Xattr
		out.CreatePath = op.ArrayInsert.CreatePath
		contents, err := contentsOrMacros(op.ArrayInsert.Content)
		if err != nil {
			return out, err
		}
		out.Contents = contents

	case *mutatein.MutateInSpec_ArrayAddUnique:
		out.Kind = "array_add_unique"
		out.Path = op.ArrayAddUnique.Path
		out.Xattr = op.ArrayAddUnique.Xattr
		out.CreatePath = op.ArrayAddUnique.CreatePath
		content, err := contentOrMacro(op.ArrayAddUnique.Content)
		if err != nil {
			return out, err
		}
		out.Content = content

	case *mutatein.MutateInSpec_Increment:
		out.Kind = "increment"
		out.Path = op.Increment.Path
		out.Xattr = op.Increment.Xattr
		out.CreatePath = op.Increment.CreatePath
		out.Delta = &op.Increment.Delta

	case *mutatein.MutateInSpec_Decrement:
		out.Kind = "decrement"
		out.Path = op.Decrement.Path
		out.Xattr = op.Decrement.Xattr
		out.CreatePath = op.Decrement.CreatePath
		out.Delta = &op.Decrement.Delta

	default:
		return out, fmt.Errorf("unsupported mutate-in spec %T", op)
	}

	return out, nil
}

func contentsOrMacros(contents []*mutatein.ContentOrMacro) ([]phpbackend.ContentOrMacro, error) {
	out := make([]phpbackend.ContentOrMacro, 0, len(contents))

	for _, content := range contents {
		wire, err := contentOrMacro(content)
		if err != nil {
			return nil, err
		}
		out = append(out, *wire)
	}

	return out, nil
}

func contentOrMacro(content *mutatein.ContentOrMacro) (*phpbackend.ContentOrMacro, error) {
	if content == nil {
		return nil, nil
	}

	switch c := content.ContentOrMacro.(type) {
	case *mutatein.ContentOrMacro_Content:
		input, err := contentToInput(c.Content)
		if err != nil {
			return nil, err
		}
		return &phpbackend.ContentOrMacro{Content: input}, nil
	case *mutatein.ContentOrMacro_Macro:
		name := c.Macro.String()
		return &phpbackend.ContentOrMacro{Macro: &name}, nil
	default:
		return nil, fmt.Errorf("unsupported mutate-in content %T", c)
	}
}
