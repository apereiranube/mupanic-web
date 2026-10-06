<?php
// Runtime snapshots live outside the deployed overlay and outside the public root.
function panicAtlasRuntimeDirectory() {
    $directory = getenv('PANIC_ATLAS_RUNTIME_DIR');
    return $directory ? rtrim($directory, '/') : '/home/mupanic/atlas-runtime';
}
function panicAtlasBalance() {
    $fallback = json_decode(file_get_contents(__DIR__.'/public-balance.json'), true);
    $path = panicAtlasRuntimeDirectory().'/public-balance.json';
    if(is_file($path) && !is_link($path) && filesize($path) <= 8000000) {
        $candidate = json_decode(file_get_contents($path), true);
        if(is_array($candidate) && isset($candidate['schemaVersion']) && $candidate['schemaVersion'] === 2 && isset($candidate['accounts'], $candidate['maps'], $candidate['drops'], $candidate['crafting'])) {
            // Upgrade reward interpretation only for byte-identical gameplay sources.
            // A changed server configuration always retains its own live reward lists.
            if(isset($candidate['sourceHash'], $fallback['sourceHash'], $fallback['eventBags']) && hash_equals($fallback['sourceHash'], $candidate['sourceHash'])) $candidate['eventBags'] = $fallback['eventBags'];
            return $candidate;
        }
    }
    return $fallback;
}
