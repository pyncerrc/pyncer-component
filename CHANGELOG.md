# Change Log

## 1.2.7 - Unreleased

### Changed

- Updated psr/http-message to 2.0.

## 1.2.6 - 2024-11-15

### Changed

- ComponenetInterface::getRequest() method now returns ServerRequestInterface instead of RequestInterface.

## 1.2.5 - 2024-03-22

### Added

- Made original model data accessible in Post and Patch item modules.

### Fixed

- Fixed possibility of request and response data missing keys.

### Changed

- Post, patch, and put item modules will now return a 204 No Content response if body is empty.

## 1.2.4 - 2023-09-06

### Fixed

- Fixed missing use statement in AbstractDeleteIndexModule.

## 1.2.3 - 2023-09-03

### Changed

- AbstractDeleteIndexModule::forgeMapperQuery() now has default implementation.

## 1.2.2 - 2023-07-29

### Fixed

- Fixed bad property references in AbstractPage and AbstractModule getPaths() function.

## 1.2.1 - 2023-07-05

### Changed

- AbstractDeleteItemModule now has a forgeMapperQuery function.

## 1.2.0 - 2023-06-08

### Added

- Any data that is not associated with a model will now be added as extra data on the model in POST, PATCH, and PUT item modules.

## 1.1.1 - 2023-05-24

### Fixed

- Missing required functions from ComponentInterface.
- Paths parameter in Module and Page components ensured to be a list array.

## 1.1.0 - 2023-05-10

### Added

- AuthorizerInterface for adding standard authorization criteria to components.
- Exposed more component properties.

### Changed

- AbstractPatchItemModule and AbstractPutItemModule now have forgeMapperQuery functions.

## 1.0.1 - 2023-01-05

### Fixed

- Some module components no longer return the wrong response object.

## 1.0.0 - 2022-12-27

Initial release.
