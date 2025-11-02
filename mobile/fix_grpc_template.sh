#!/bin/bash

# Fix gRPC-Core and gRPC-C++ template parsing issue for Xcode 16.4

echo "🔧 Fixing gRPC template issues..."

GRPC_CORE_FILE="ios/Pods/gRPC-Core/src/core/lib/promise/detail/basic_seq.h"
GRPC_CPP_FILE="ios/Pods/gRPC-C++/src/core/lib/promise/detail/basic_seq.h"

# Fix gRPC-Core
if [ -f "$GRPC_CORE_FILE" ]; then
    cp "$GRPC_CORE_FILE" "$GRPC_CORE_FILE.backup"
    sed -i '' 's/Traits::template CallSeqFactory/Traits::CallSeqFactory/g' "$GRPC_CORE_FILE"
    echo "✅ Fixed gRPC-Core template issue"
else
    echo "⚠️  gRPC-Core file not found"
fi

# Fix gRPC-C++
if [ -f "$GRPC_CPP_FILE" ]; then
    cp "$GRPC_CPP_FILE" "$GRPC_CPP_FILE.backup"
    sed -i '' 's/Traits::template CallSeqFactory/Traits::CallSeqFactory/g' "$GRPC_CPP_FILE"
    echo "✅ Fixed gRPC-C++ template issue"
else
    echo "⚠️  gRPC-C++ file not found"
fi

echo "🎉 All gRPC template issues fixed!"
