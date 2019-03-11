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
| unshift | 6405.9 | N/A | **1.0 | 22.1 |
| `serialize` | 104.9 | 140.5 | 62.0 | 1.0 |
| `unserialize` | 109.3 | 182.6 | 52.1 | 1.0 |

Data for 100,000 single-byte elements.

How to read this table: `foreach` over a bytemap takes 3.1 times the time it takes to iterate over an array with the same 100,000 single-byte elements.

\* Data for `\Ds\Deque` (in this test, `\Ds\Vector` took 40.8 times the time it took to shift using `\Ds\Deque`).

\*\* Data for `\Ds\Deque` (in this test, `\Ds\Vector` took 216.2 times the time it took to unshift using `\Ds\Deque`).

### Batch operations

| Operation | array | SPL | DS | Bytemap |
| :-- | --: | --: | --: | --: | --: |
| delete at head | 374.9 | 858.2 | 85.9 | 1.0 |
| delete at tail | 1032.9 | 1.0 | 12.0 | 2.6 |
| insert at head | 23.5 | 74.1 | *1.0 | 1.9 |
| insert at tail | 1.8 | 1.9 | 1.0 | 2.5 |

Deleted and (in a separate test) inserted a sequence of batches containing 1 to 1000 single-byte elements. The same sequence of batches was used for every benchmarked container. The sequence had 50,606 elements in total.

\* Data for `\Ds\Deque` (in this test, `\Ds\Vector` took 1.5 times the time it took to insert at head using `\Ds\Deque`).

### Finding elements

| Operation | Elements sought | array | SPL | DS | Bytemap |
| :-- | :-- | --: | --: | --: | --: |
| existence check | 1 out of 1| 1.0 | 5.0 | 1.0 | 5.0 |
| existence check | 1 out of 10 | 1.0 | 1.7 | 1.4 | 1.8 |
| existence check | 100 out of 100 | 14.5 | 40.5 | 33.1 | 1.0 |
| find first index | 1 out of 1 | 1.1 | 5.9 | 1.0 | 4.5 |
| find all indexes | 100 out of 100 | 1.0 | 1.4 | 1.2 | 1.4 |
| grep | 100 out of 100 | 1.0 | 1.8 | 1.7 | 1.7 |

Data for 100,000 single-byte elements.

### JSON

| Operation | Element count | Element length | Time |
| :-- | --: | --: |
| parsing | 100,000 | 1 | 0.02 s |
| parsing | 100,000 | 4 | 0.02 s |
| parsing | 1,000,000 | 1 | 0.19 s |
| parsing | 1,000,000 | 4 | 0.24 s |
| streaming | 100,000 | 1 | 0.01 s |
| streaming | 100,000 | 4 | 0.02 s |
| streaming | 1,000,000 | 1 | 0.10 s |
| streaming | 1,000,000 | 4 | 0.15 s |

Data for bytemaps only.

## Author

Robert Gust-Bardon - <robert@gust-bardon.org> - <https://twitter.com/RGustBardon>

## License

Bytemap is licensed under the MIT License - see the `LICENSE` file for details.