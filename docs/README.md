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

In the tables that follow, _array_ is the native PHP array, _SPL_ stands for `\SplFixedArray` and _DS_ stands for `\Ds\Sequence` (i.e. `\Ds\Deque` and `\Ds\Vector`; with ext-ds).

### Memory

| Length | array | SPL | DS | Bytemap |
| --: | --: | --: | --: | --: | --: |
| 1 | 4.0 MB | 1.5 MB | 2.0 MB | 0.1 MB |
| 4 | 4.0 MB | 1.5 MB | 2.0 MB | 0.4 MB |

Data for 100,000 elements. _Length_ is the number of characters in each element.

### Time

#### Elementary operations

| Operation | array | SPL | DS | Bytemap |
| :-- | --: | --: | --: | --: | --: |
| `foreach` | 1.0 | 1.7 | 1.5 | 3.1 |
| pop | 1.0 | 1.6 | 1.2  | 32.2 |
| push | 1.0 | 1.7 | 1.3 | 3.3 |
| random read | 1.2 | 1.0 | 1.0 | 2.2 |
| random write | 1.1 | 1.0 | 1.3 | 2.4 |
| shift | 1.0 | N/A | *1.6 | 6.6 |
| `serialize` | 104.9 | 140.5 | 62.0 | 1.0 |
| `unserialize` | 109.3 | 182.6 | 52.1 | 1.0 |

How to read this table: `foreach` over a bytemap is 3.1 times slower than over an array with the same elements.

\* Data for `\Ds\Deque` (`\Ds\Vector` is 40.8 times slower than an array when shifting).

### Batch operations

| Operation | array | SPL | DS | Bytemap |
| :-- | --: | --: | --: | --: | --: |
| deletion at head | 374.9 | 858.2 | 85.9 | 1.0 |
| deletion at tail | 1032.9 | 1.0 | 12.0 | 2.6 |
| insertion at head | 156.2 | 544.6 | *1.0 | 2.8 |
| insertion at tail | 1.8 | 1.9 | 1.0 | 2.5 |

When deleting, the size of batches varied between 1 and 1000, but the same sequence of batches was used for every benchmarked container.

When inserting, the size of batches varied between 1 and 95, but but the same sequence of batches was used for every benchmarked container.

\* Data for `\Ds\Deque` (`\Ds\Vector` is 8.7 times slower than `\Ds\Deque` when inserting at head).

## Author

Robert Gust-Bardon - <robert@gust-bardon.org> - <https://twitter.com/RGustBardon>

## License

Bytemap is licensed under the MIT License - see the `LICENSE` file for details.