# Changelog

## master
[master...v4.0.2 ](https://github.com/deployphp/deployer/compare/v4.0.2...master)

### Added
- Added `testLocally` function (analog `test` fn)

### Changed
- Server config `setPty` renamed to `pty` #953
- Raised timeout for runLocally to 300 seconds #955
- `deploy:unlock` now always successful #950
- Added option `-L` to `setfacl` #956
- Run `deploy:unlock` when deploy is fail #958

### Fixed
- Fixed native ssh scp option
- Fixed bug with `$httpGroup` guard clause #948

## v4.0.2
🙄
