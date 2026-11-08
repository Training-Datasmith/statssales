<?php

$guardFile = getenv('GUARD_FILE');
$sentinel = getenv('GUARD_SENTINEL');

register_shutdown_function(function () use ($sentinel) {
    if ($sentinel) {
        file_put_contents($sentinel, 'exited');
    }
});

if (!$guardFile || !file_exists($guardFile)) {
    if ($sentinel) {
        file_put_contents($sentinel, 'fell-through');
    }
    exit(1);
}

include $guardFile;

if ($sentinel) {
    file_put_contents($sentinel, 'fell-through');
}
