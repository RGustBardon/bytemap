# bytemap - Lean data structures for PHP

[![Travis CI Build Status](https://travis-ci.org/RGustBardon/bytemap.svg?branch=master)](https://travis-ci.org/RGustBardon/bytemap)
[![AppVeyor Build Status](https://ci.appveyor.com/api/projects/status/github/RGustBardon/bytemap?branch=master&svg=true)](https://ci.appveyor.com/project/RGustBardon/bytemap)
[![Coverage Status](https://coveralls.io/repos/github/RGustBardon/bytemap/badge.svg?branch=master)](https://coveralls.io/github/RGustBardon/bytemap?branch=master)

## Differences to built-in arrays

- Each element of a bytemap is a scalar of the same type and the same length.
- The index of the first element of a bytemap is `0`.
- The index of any other element is the index of the previous element plus one.
- A default value is assigned to all the elements that have not been assigned yet.
- The internal representation of a bytemap is a string.

### Author

Robert Gust-Bardon - <robert@gust-bardon.org> - <https://twitter.com/RGustBardon>

### License

Bytemap is licensed under the MIT License - see the `LICENSE` file for details.