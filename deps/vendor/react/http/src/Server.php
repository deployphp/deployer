<?php

namespace React\Http;

// Deprecated `Server` is an alias for new `HttpServer` to ensure existing code continues to work as-is.
\class_alias(__NAMESPACE__ . '\\HttpServer', __NAMESPACE__ . '\\Server', true);

// Aid static analysis and IDE autocompletion about this deprecation,
// but don't actually execute during runtime because `HttpServer` is final.
if (!\class_exists(__NAMESPACE__ . '\\Server', false)) {
    /**
     * @deprecated 1.5.0 See HttpServer instead
     * @see HttpServer
     */
    final class Server extends HttpServer
    {
    }
}
