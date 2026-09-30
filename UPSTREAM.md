# WebEngine upstream

MU PANIC uses WebEngine CMS as its base engine.

- Upstream: https://github.com/lautaroangelico/WebEngine
- Version: 1.2.7
- Pinned upstream commit: `5cf16f1284abb970e29bde2a937dbec412d1bc94`
- License: MIT

The upstream CMS is not edited directly in this repository.

Custom MU PANIC files live in `overlay/`. The beta build workflow downloads the pinned WebEngine revision, applies the overlay, and publishes the deployable site to the `deploy-beta` branch.

This keeps the original WebEngine source reproducible and makes MU PANIC-specific changes easy to review.
