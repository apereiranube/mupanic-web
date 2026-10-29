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
            // Deploy the latest confirmed hunting migration even when hosting still
            // holds an older runtime snapshot. Preserve its economy/progression.
            // Any later signed server export takes precedence over this fallback.
            $hunting = $fallback['hunting'] ?? null;
            $candidateStamp = $candidate['hunting']['generatedAt'] ?? $candidate['generatedAt'];
            if($hunting && strtotime($candidateStamp) < strtotime($hunting['generatedAt'])) {
                foreach(['maps','drops','eventBags'] as $key) $candidate[$key] = $fallback[$key];
                $candidate['hunting'] = $hunting;
                $candidate['revision'] = max($candidate['revision'], $fallback['revision']);
            } elseif(!$hunting && isset($candidate['sourceHash'], $fallback['sourceHash'], $fallback['eventBags']) && hash_equals($fallback['sourceHash'], $candidate['sourceHash'])) {
                $candidate['eventBags'] = $fallback['eventBags'];
            }
            // An exporter older than this overlay may not send every mix rate yet:
            // fill only the missing keys; synced rates always win.
            if(isset($fallback['crafting']['mixRates']) && is_array($candidate['crafting']['mixRates'] ?? null)) {
                foreach($fallback['crafting']['mixRates'] as $rateKey=>$rateValues) if(!isset($candidate['crafting']['mixRates'][$rateKey])) $candidate['crafting']['mixRates'][$rateKey] = $rateValues;
            }
            return $candidate;
        }
    }
    return $fallback;
}
