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

## Performance

### Memory

A bytemap of 100,000 single-character elements takes 100 kB
(SplFixedArray: 1.53 MB, DsSequence: 2.00 MB, array: 4.00 MB).

If the elements are four characters long, then a bytemap takes 392 kB,
(again, SplFixedArray: 1.53 MB, DsSequence: 2.00 MB, array: 4.00 MB).

### Time

#### Elementary operations

| Operation | array | \SplFixedArray | \Ds\Deque | \Ds\Vector | Bytemap |
| :-- | --: | --: | --: | --: | --: |
| foreach | 1.0 | 1.7 | 1.5 | 1.4 | 3.1 |
| pop | 1.0 | 1.6 | 1.2 | 1.2 | 32.2 |
| push | 1.0 | 1.7 | 1.3 | 1.3 | 3.3 |
| random read | 1.2 | 1.0 | 1.0 | 1.1 | 2.2 |
| random write | 1.1 | 1.0 | 1.3 | 1.0 | 2.4 |
| shift | 1.0 | N/A | 1.6 | 40.8 | 6.6 |
| serialize | 104.9 | 140.5 | 62.0 | 63.1 | 1.0 | 
| unserialize | 109.3 | 182.6 | 52.1 | 56.8 | 1.0 | 

## Author

Robert Gust-Bardon - <robert@gust-bardon.org> - <https://twitter.com/RGustBardon>

## License

Bytemap is licensed under the MIT License - see the `LICENSE` file for details.