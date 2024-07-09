# GQUL - Get QQ Update Link

**GQUL** is a PHP-based tool designed to fetch update links for various QQ versions, including LiteGray testing versions.

## Features
- Retrieve QQ update links for different versions.
- Supports LiteGray testing versions.

## Usage
- Not every visit succeeds in fetching the updated data!!!
- Actually it's not simple, QQ distributes the test content via uin and version, and not every uin is successfully fetched.
- Accessed via get spliced with the correct parameters:
```get
send.php?uin=114514&version=9.0.70%236676%230
```
- `uin`: A QQ number  e.g:114514
- `version`: Current version information (requires url encoding)  e.g:9.0.70%236676%230

## Files
- `core.php`: Core functionalities.
- `curl.php`: CURL operations.
- `send.php`: Main script to fetch the update link.
- `util.php`: Utility functions.
